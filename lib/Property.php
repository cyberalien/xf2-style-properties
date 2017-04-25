<?php

namespace CyberAlien\XFStyleProperties;

use \CyberAlien\SimpleTokenizer\Tokenizer;

class Property {
    protected $name;
    protected $data = null;

    /**
     * List of attributes used for each component
     *
     * (Sort of) copied from \XF\Style::compileCssPropertyValue
     */
    const cssComponents = [
        'text' => ['font-size', 'color', 'font-weight', 'font-style', 'text-decoration'],
        'background' => ['background-color', 'background-image'],
        'border'    => [
            'border-width', 'border-color',
            'border-top-width', 'border-top-color',
            'border-right-width', 'border-right-color',
            'border-bottom-width', 'border-bottom-color',
            'border-left-width', 'border-left-color',
            ],
        'border_width_simple'   => ['border-width'],
        'border_color_simple'   => ['border-color'],
        'border_radius_simple'  => ['border-radius'],
        'border_radius' => ['border-radius', 'border-top-left-radius', 'border-top-right-radius', 'border-bottom-right-radius', 'border-bottom-left-radius'],
        'padding'   => ['padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left'],
        'extra' => ['extra']
    ];

    /**
     * Constructor
     *
     * @param string $name
     * @param string|array $data Property data
     */
    function __construct($name, $data)
    {
        $this->name = $name;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->data = $data;
    }

    /**
     * Set value
     *
     * @param $value
     */
    public function setValue($value)
    {
        if ($this->data['property_type'] === 'css' && is_string($value)) {
            $this->data['property_value'] = $value === '' ? [] : json_decode($value, true);
        } else {
            $this->data['property_value'] = $value;
        }
    }

    /**
     * Get value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->data['property_value'];
    }

    /**
     * Set data from LESS string
     *
     * @param $data
     * @return boolean
     */
    public function fromLess($data)
    {
        $tokenizer = new Tokenizer([
            'lessSyntax' => true,
            'splitRules' => true,
        ]);

        $tokens = $tokenizer->tree($data);

        if ($this->data['property_type'] !== 'css') {
            // Import scalar variable
            $expected = '@xf-' . $this->name;
            foreach ($tokens as $token) {
                if ($token['token'] === 'rule' && $token['key'] === $expected) {
                    // Found it
                    $this->data['property_value'] = $token['value'];
                    return true;
                }
            }
            return false;
        }

        // Parse all tokens for extract CSS style property data
        $data = [];
        foreach ($tokens as $token) {
            switch ($token['token']) {
                case '{':
                    // Extra code as mixin for CSS properties
                    $expected = '.xf-' . $this->name . '--extra()';
                    if ($token['code'] !== $expected) {
                        break;
                    }

                    $value = trim(Tokenizer::build(
                        $token['children'],
                        [
                            'minify'    => false,
                            'newLineAfterSelector'  => false,
                            'tab'   => '  '
                        ]
                    ));
                    if (strlen($value)) {
                        $data['extra'] = $value;
                    }
                    break;

                case 'rule':
                    // Possible variable
                    $expected = '@xf-' . $this->name . '--';
                    if (substr($token['key'], 0, strlen($expected)) !== $expected) {
                        break;
                    }

                    $key = substr($token['key'], strlen($expected));
                    if (!$this->_validCSSComponent($key)) {
                        break;
                    }

                    $data[$key] = $token['value'];
                    break;

                default:
                    // Commented code or bad code - ignore it
            }
        }

        $this->data['property_value'] = $data;
        return count($data) > 0;
    }

    /**
     * Export property as LESS code
     *
     * @param bool $includeMissingComponents True if missing components in CSS property should be included
     * @return string
     */
    public function exportLessCode($includeMissingComponents = true)
    {
        switch ($this->data['property_type']) {
            case 'css':
                return $this->_exportLessMixin($includeMissingComponents);

            default:
                return $this->_exportLessVariable($this->data['property_value'], '');
        }
    }

    /**
     * Export variable
     *
     * @param $value
     * @param string $suffix
     * @return string
     */
    protected function _exportLessVariable($value, $suffix = '')
    {
        return ($value === '' ? '// ' : '') . '@xf-' . $this->name . ($suffix === '' ? '' : '--' . $suffix) . ': ' . $value . ";\n";
    }

    /**
     * Export mixin
     *
     * @param bool $includeMissingComponents
     * @return string
     */
    protected function _exportLessMixin($includeMissingComponents)
    {
        $result = '';
        $included = [];

        foreach ($this->data['css_components'] as $component) {
            // New line before each section
            $newLine = $result === '' ? '' : "\n";

            foreach (self::cssComponents[$component] as $key) {
                // Some components are listed in multiple groups
                if (in_array($key, $included)) {
                    continue;
                }
                $included[] = $key;

                // Get value
                $value = isset($this->data['property_value'][$key]) ? $this->data['property_value'][$key] : '';
                switch ($key) {
                    case 'extra':
                        $value = $value === '' ? '' : "\n\t" . str_replace("\n", "\n\t", $value);
                        $result .= $newLine . '.xf-' . $this->name . '--extra()' . "\n{" . $value . "\n}\n";
                        $newLine = '';
                        break;

                    default:
                        if ($includeMissingComponents || isset($this->data['property_value'][$key])) {
                            $result .= $newLine . $this->_exportLessVariable($value, $key);
                            $newLine = '';
                        }
                }

            }
        }

        return $result;
    }

    /**
     * Check if component is available for current css property
     *
     * @param string $component
     * @return bool
     */
    protected function _validCSSComponent($component)
    {
        foreach ($this->data['css_components'] as $block) {
            foreach (self::cssComponents[$block] as $attr) {
                if ($attr === $component) {
                    return true;
                }
            }
        }
        return false;
    }
}
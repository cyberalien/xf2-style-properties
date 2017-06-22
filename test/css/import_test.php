<?php

use \CyberAlien\XFStyleProperties\Property;

class CSSErrorsTest extends \PHPUnit\Framework\TestCase {
    /**
     * Get property with empty value
     *
     * @param string $name
     * @return Property
     */
    protected function _getProperty($name = 'publicFooter')
    {
        $data = json_decode(file_get_contents(__DIR__ . '/publicFooter.json'), true);
        $prop = new Property($name, $data);
        $prop->setValue('');
        return $prop;
    }

    /**
     * Test simple custom value
     */
    public function testSimpleImport()
    {
        $prop = $this->_getProperty('test');

        $prop->fromLess('
@xf-test--color: red;
@xf-test--border-width: 2px;
@xf-test--padding-left: 10px;
        ');
        $result = $prop->getValue();
        $this->assertEquals([
            'color' => 'red',
            'border-width'  => '2px',
            // no padding - property does not have padding
        ], $result);
    }

    /**
     * Test complex values
     */
    public function testComplexImport()
    {
        $prop = $this->_getProperty('test');

        $prop->fromLess('
@xf-test--color: rgba(0, 0, 0, max(@xf-test-opacity + .5, 1)); // nested functions with variables
@xf-test--font-style: ; // empty rule should be ignored
@xf-test--border-width: 2px 10px !important; // !important should be ignored
        ');
        $result = $prop->getValue();
        $this->assertEquals([
            'color' => 'rgba(0, 0, 0, max(@xf-test-opacity + .5, 1))',
            'border-width'  => '2px 10px'
        ], $result);
    }

    /**
     * Test mixin with nested style
     */
    public function testNestedMixin()
    {
        $prop = $this->_getProperty('test');

        $prop->fromLess('
.xf-test--extra() {
    opacity: 0;
    &:hover {
        opacity: 1;
    }
}
// Add color after mixin
@xf-test--color: darkBlue;
        ');
        $result = $prop->getValue();
        $this->assertEquals([
            'color' => 'darkBlue',
            'extra'  => 'opacity: 0;
&:hover {
  opacity: 1;
}'
        ], $result);
        $this->assertTrue($prop->updated);
    }

	/**
	 * Test exporting and importing same css property
	 */
    public function testUpdatingInput()
    {
    	$defaultValue = '{"font-size":"@xf-fontSizeNormal","color":"@xf-textColor","background-color":"mix(@xf-contentBg, @xf-paletteColor1, 60%)","border-width":"@xf-borderSize","border-radius":"@xf-borderRadiusMedium","border-top-color":"@xf-borderColorHeavy","border-right-color":"@xf-borderColorLight","border-bottom-color":"@xf-borderColorLight","border-left-color":"@xf-borderColorHeavy","padding":"@xf-paddingMedium"}';

	    $prop = new Property('input', [
	    	'property_name' => 'input',
	    	'property_type' => 'css',
	    	'css_components'    => 'text,background,border,border_radius,padding,extra',
	    	'property_value'    => json_decode($defaultValue, true)
	    ]);
	    $less = $prop->exportLessCode(true);
	    $prop->fromLess($less);

	    $list1 = json_decode($defaultValue, true);
	    $list2 = $prop->getValue();
	    foreach ($list1 as $key => $oldValue) {
	    	if (!isset($list2[$key])) {
	    		echo 'Missing key: ', $key, "\n";
		    } elseif ($list2[$key] !== $list1[$key]) {
	    		echo 'Different values for key ', $key, ': ', $list1[$key], ' and ', $list2[$key], "\n";
		    }
	    }
	    foreach ($list2 as $key => $newValue) {
	    	if (!isset($list1[$key])) {
	    		echo 'Missing key2: ', $key, "\n";
		    }
	    }

	    $this->assertFalse($prop->updated);
    }
}
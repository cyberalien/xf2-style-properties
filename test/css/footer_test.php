<?php

use \CyberAlien\XFStyleProperties\Property;

class CSSFooterTest extends \PHPUnit\Framework\TestCase {
    protected function _getLess()
    {
        return '@xf-publicFooter--font-size: @xf-fontSizeSmall;
@xf-publicFooter--color: @xf-linkColor;
// @xf-publicFooter--font-weight: ;
// @xf-publicFooter--font-style: ;
// @xf-publicFooter--text-decoration: ;

@xf-publicFooter--background-color: xf-intensify(@xf-paletteColor5, 12%);
// @xf-publicFooter--background-image: ;

// @xf-publicFooter--border-width: ;
// @xf-publicFooter--border-color: ;
// @xf-publicFooter--border-top-width: ;
// @xf-publicFooter--border-top-color: ;
// @xf-publicFooter--border-right-width: ;
// @xf-publicFooter--border-right-color: ;
// @xf-publicFooter--border-bottom-width: ;
// @xf-publicFooter--border-bottom-color: ;
// @xf-publicFooter--border-left-width: ;
// @xf-publicFooter--border-left-color: ;

.xf-publicFooter--extra()
{
' . "\t" . 'cursor: pointer;
}
';
    }

    protected function _getArray()
    {
        return json_decode(file_get_contents(__DIR__ . '/publicFooter.json'), true);
    }

    /**
     * Test export
     */
    public function testExport()
    {
        $data = $this->_getArray();
        $less = $this->_getLess();

        $prop = new Property('publicFooter', $data);

        // Export and compare
        $result = $prop->exportLessCode();
        $this->assertEquals($less, $result);
    }

    /**
     * Test import
     */
    public function testImport()
    {
        $data = $this->_getArray();
        $less = $this->_getLess();

        $prop = new Property('publicFooter', $data);

        // Reset value
        $prop->setValue('');
        $result = $prop->exportLessCode();
        $this->assertNotEquals($less, $result);

        // Import, export and compare
        $prop->fromLess($less);
        $result = $prop->getValue();
        $this->assertEquals($data['property_value'], $result);
    }
}
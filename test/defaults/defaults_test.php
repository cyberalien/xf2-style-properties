<?php

use \CyberAlien\XFStyleProperties\Property;

class DefaultsTest extends \PHPUnit\Framework\TestCase {
    public function testDefaultProperties()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/style_properties.json'), true);

        foreach ($data as $item) {
            $name = $item['property_name'];
            $defaultValue = $item['property_value'];

            $prop = new Property($name, $item);
            $exported = $prop->exportLessCode();

            // Reset and make sure it has been reset
            $prop->setValue('');
            if ($defaultValue !== '') {
                $this->assertNotEquals($defaultValue, $prop->getValue(), 'Failed resetting property ' . $name);
            }

            // Import
            $prop->fromLess($exported);
            $this->assertEquals($defaultValue, $prop->getValue(), 'Failed importing property ' . $name);
        }
    }
}
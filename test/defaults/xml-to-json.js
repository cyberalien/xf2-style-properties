/**
 * Script for converting style_properties.xml to style_properties.json
 *
 * It converts XML to array of data, similar to XML import in XenForo 2
 *
 * Installation:
 *  npm install -g cheerio
 *
 * Usage:
 *  node xml-to-json
 */
const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');

/**
 * Do stuff
 */
try {
    let data = fs.readFileSync('style_properties.xml', 'utf8');
} catch (err) {
    console.log('Copy style_properties.xml from XenForo to generate JSON file');
    return;
}

let properties = [];

let $ = cheerio.load(data, {
    lowerCaseAttributeNames: false,
    lowerCaseTags: false,
    xmlMode: true
});


$('style_property').each((index, item) => {
    let property = Object.assign({}, item.attribs);

    // Get children
    cheerio(item).children().each((index, child) => {
        property[child.tagName] = cheerio(child).text();
    });

    // Clean up stuff to match XF2 code
    switch (property.property_type) {
        case 'css':
            property.value = JSON.parse(property.value);
            property.css_components = property.css_components.split(',');
            break;

        default:
            if (property.value.length > 1 && property.value.slice(0, 1) === '"' && property.value.slice(-1) === '"') {
                property.value = property.value.slice(1, property.value.length - 1);
            }
    }

    // Rename value to property_value
    property.property_value = property.value;
    delete property.value;

    properties.push(property);
});

fs.writeFileSync('style_properties.json', JSON.stringify(properties, null, 4));
console.log('Found ' + properties.length + ' properties.');

BubbleHouse Integration Iframe Widget
=====================================

This Magento 2 widget allows you to display an iframe for the BubbleHouse Block API.
You can customize the iframe by specifying parameters such as the page name and the height of the iframe.

Widget Configuration
--------------------

The widget is defined in `etc/widget.xml` as follows:

<widget class="BubbleHouse\Integration\Block\Widget\Iframe" id="bubblehouse_block_api">
    <label>BubbleHouse Integration Iframe</label>
    <description>BubbleHouse Block API iFrames</description>
    <parameters>
        <parameter name="page" xsi:type="text" required="false" visible="true" sort_order="10">
            <label>Page</label>
            <value>Rewards7</value>
        </parameter>
        <parameter name="height" xsi:type="text" required="false" visible="true" sort_order="10">
            <label>iFrame Height</label>
            <value>1700</value>
        </parameter>
    </parameters>
</widget>

Widget Parameters
-----------------

- page: Text parameter (default "Rewards7")  
  Enter the page (or endpoint) name that the iframe should load.

- height: Text parameter (default "1700")  
  Define the height of the iframe in pixels.

How to Use the Widget
---------------------

1. Embedding in a `.phtml` Template:

<?php
echo $block->getLayout()
    ->createBlock(\BubbleHouse\Integration\Block\Widget\Iframe::class)
    ->setTemplate('BubbleHouse_Integration::widget/iframe.phtml')
    ->setData([
	'title' => 'some title',
        'page'   => 'MyCustomPage',
        'height' => '1200'
    ])
    ->toHtml();
?>

2. Adding the Widget to a CMS Page or CMS Block:

Use this directive in the content editor:

{{widget type="BubbleHouse\Integration\Block\Widget\Iframe" page="MyCustomPage" height="1200"}}

3. Inserting the Widget Using the Admin “Insert Widget” Tool:

- Go to Content > Pages (or Blocks) and edit a page or block.
- Click "Insert Widget".
- Select "BubbleHouse Integration Iframe".
- Set parameters: Page and iFrame Height.
- Click Insert Widget. Magento will insert the directive as shown above.

Final Notes
-----------

- Caching: Flush Magento caches after adding or updating widgets.
- Template Location: Make sure the widget template `iframe.phtml` is located in `view/frontend/templates/widget/`.

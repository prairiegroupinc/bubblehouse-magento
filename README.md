Bubblehouse Integration Iframe Widget
=====================================

This Magento 2 widget allows you to display an iframe for the BubbleHouse Block API.
You can customize the iframe by specifying parameters such as the page name and the height of the iframe.

Widget Configuration
--------------------

The widget is defined in `etc/widget.xml` as follows:

```xml
<widget class="BubbleHouse\Integration\Block\Widget\Iframe" id="bubblehouse_block_api">
    <label>Bubblehouse Integration Iframe</label>
    <description>Bubblehouse Block API iFrames</description>
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
```

Widget Parameters
-----------------

- `page`: Text parameter (default "Rewards7")
  Enter the page (or endpoint) name that the iframe should load.

- `height`: Text parameter (default "1700")
  Define the height of the iframe in pixels.

How to Use the Widget
---------------------

1. Embedding in a `.phtml` Template:

```php
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
```

2. Adding the Widget to a CMS Page or CMS Block:

Use this directive in the content editor:

```
{{widget type="BubbleHouse\Integration\Block\Widget\Iframe" page="MyCustomPage" height="1200"}}
```

3. Inserting the Widget Using the Admin “Insert Widget” Tool:

- Go to Content > Pages (or Blocks) and edit a page or block.
- Click "Insert Widget".
- Select "Bubblehouse Integration Iframe".
- Set parameters: Page and iFrame Height.
- Click Insert Widget. Magento will insert the directive as shown above.

Final Notes
-----------

- Caching: Flush Magento caches after adding or updating widgets.
- Template Location: Make sure the widget template `iframe.phtml` is located in `view/frontend/templates/widget/`.

Inbound Requests
----------------

Coupon creation requires an authorized Magento access token with the
**Bubblehouse Integration > Bubblehouse SalesRules** permission. Configure the
Bubblehouse integration to include this token in its requests.

Bubblehouse can send authorized requests to:

```text
POST /rest/V1/bubblehouse/update-customer-attributes
```

Create and activate a Magento integration with the **Bubblehouse Integration > Receive Bubblehouse Requests**
resource, then send its access token as a bearer token over HTTPS:

```bash
curl --request POST 'https://magento.example/rest/V1/bubblehouse/update-customer-attributes' \
    --header 'Authorization: Bearer <integration-access-token>' \
    --header 'Content-Type: application/json' \
    --data '{"customer_email":"customer@example.com","website_id":2,"bh_tier":"Gold","bh_points_balance":125.5}'
```

The request matches the Magento customer by `customer_email` and `website_id`, then saves `bh_tier` and
`bh_points_balance` as custom attributes. Requests with missing, invalid, revoked, or insufficiently privileged tokens
are rejected by Magento before the handler is called.

Successful requests return:

```json
{
    "status": "ok"
}
```

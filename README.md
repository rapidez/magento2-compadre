# Magento Rapidez Compadre

This **Magento Module** will allow Rapidez to support more functionalities.

## Current functionality

### ProductStockItem
The ProductStockItem type is added to the ProductInterface in the GraphQL definition.
Allowing you to retrieve the stock information of the product:
```diff
    product {
        id,
        sku,
        name,
        type_id,
        url_key,
        url_suffix,
+       stock_item {
+           max_sale_qty
+           min_sale_qty
+           qty_increments
+           in_stock
+      }
    }
```

### Sales rule labels
The SalesRuleLabel type is added to the CartItemInterface in the GraphQL definition.
Allowing you to retrieve the sales rule labels that are applied to a quote item:
```diff
    items {
        id
        quantity
+       sales_rule_labels {
+           name
+           description
+           store_label
+           discount_amount
+           from_date
+           to_date
+       }
    }
```

### Other
And it extends Magento functionality in order to facilitate file upload product options via GraphQL

## Installation

In your Magento installation run
```bash
composer require rapidez/magento2-compadre
bin/magento module:enable Rapidez_Compadre
```

## Configuration

Configuration options are available under `Stores > Configuration > Rapidez > Config`

Here you can configure what extra fields should be exposed in the GraphQL ProductStockItem, fields not exposed will be `null`.

## Release instructions

If GraphQL changes have been made src/etc/module.xml must be updated with the new release version number.
This way we can easily detect which fields should be available in GraphQL for use. As Introspection is disabled outside developer mode.

## License

GNU General Public License v3. Please see [License File](LICENSE) for more information.

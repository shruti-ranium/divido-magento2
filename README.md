# Divido Magento 2 Extension

The Divido Extension for Magento 2 enables you to add Divido financing to you checkout payment options.

## Divido subscription
Visit https://www.divido.com/sign-up/ to sign up for a demo account.

##Installation
### Install with composer

```
$ cd /path/to/magento
$ composer require divido/divido-magento2
$ php bin/magento setup:upgrade
```

## Setup
After getting a Divido subscription, only two settings need to be made, in order to enable the Divido option at checkout.

In `Stores > Configuration > Sales > Payment Methods` you will find the **Divido Financing** option.
  
Enter the API-key from your Divido account in the field **API-key**  
Set the field **Enabled** to **Yes**

That should be it to get going with Divido as a checkout option.

### Setup fields description

| Field | Description |
| --- | --- |
| API-key | Obtained from Divido, needed to identify your shop in communications with the Divido system |
| Shared secret | Obtained from Divido, enables message signing. |
| Enabled | Enables / Disables both the product page calculator and checkout option |
| Debug | Logs useful information when troubleshooting |
| Title | The title of the checkout option |
| Create order on | Decide at what stage in the Divido process you want to create the order and reserve stock |
| New order status | What status a new order created through Divido will have |
| Automatic fulfilment | Notify Divido and the lender that an order has been fulfilled |
| Fulfilment status | Set the status at which an order is considered fulfilled |
| Minimum cart amount | Under this amount, Divido is not available as a checkout option |
| Product selection | Decide what products are available on finance |
| Displayed plans | Decide what plans are globally available |
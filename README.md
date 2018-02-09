# grc-romanian-fields
Wordpress/Woocommerce - Romanian checkout fields with CIF validation on ANAF API

Add 4 custom fields for Romanian Legislation:
- CIF/CUI
- Registration number
- Bank account number
- Bank name

When the checkout is submitted it checks the CIF/CUI with the ANAF API to see if it is a valid firm: https://webservicesp.anaf.ro/PlatitorTvaRest/api/v1/

Also the fields are displayed in the adress billing beneath Company name - on chekcout page, thank you page, admin orders and emails.

You can also modify this fields as admin through orders or as a user from my-account page -> my-adresses.

Preview: https://drive.google.com/file/d/1AjbLy-QCbsmUWveCcPVC_bA_XfYja0Qk/view?usp=drivesdk

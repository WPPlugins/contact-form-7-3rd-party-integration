=== Contact-Form-7: 3rd-Party Integration ===
Contributors: atlanticbt, zaus
Donate link: http://atlanticbt.com
Tags: contact form, form, contact form 7, CF7, CRM, mapping, 3rd-party service, services, remote request
Requires at least: 2.8
Tested up to: 3.3.1
Stable tag: trunk
License: GPLv2 or later

Send Contact Form 7 submissions to a 3rd-party Service, like a CRM.  Multiple configurable services, custom field mapping, pre/post processing.

== Description ==

_(Please note this plugin has been "replaced" by [Forms: 3rd-Party Integration][http://wordpress.org/plugins/forms-3rdparty-integration/], which integrates with Gravity Forms in addition to Contact Form 7)_

Send [Contact Form 7][] Submissions to a 3rd-party Service, like a CRM.  Multiple configurable services, custom field mapping.  Provides hooks and filters for pre/post processing of results.  Allows you to send separate emails, or attach additional results to existing emails.  Comes with a couple examples of hooks for common CRMs (listrak, mailchimp, salesforce).

The plugin essentially makes a remote request (POST) to a service URL, passing along remapped form submission values.

Includes hidden field plugin from [Contact Form 7 Modules: Hidden Fields][].  Based on idea by Alex Hager "[How to Integrate Salesforce in Contact Form 7][]"

[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"
[Contact Form 7 Modules: Hidden Fields]: http://wordpress.org/extend/plugins/contact-form-7-modules/ "Hidden Fields from CF7 Modules"
[How to Integrate Salesforce in Contact Form 7]: http://www.alexhager.at/how-to-integrate-salesforce-in-contact-form-7/ "Original Inspiration"

== Installation ==

1. Unzip, upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Make sure [Contact Form 7][] is installed
3. Activate plugin
4. Go to new admin subpage _"3rdparty Services"_ under the CF7 "Contact" menu and configure services + field mapping.

Please note that this includes an instance of `hidden.php`, which is part of the "Contact Form 7 Modules" plugin -- this will show up on the Plugin administration page, but is included automatically, so you don't need to enable it.  This file will only be included if you don't already have the module installed.

[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"

== Frequently Asked Questions ==

= How do I add / configure a service? =

See [Screenshots][] for visual examples.

Essentially,

1. Name your service
2. Enter the submission URL -- if your "service" provides an HTML form, you would use the form action here
3. Choose which forms will submit to this service ("Attach to Forms")
4. Set the default "success condition", or leave blank to ignore (or if using post processing, see [Hooks][] - this just looks for the provided text in the service response, and if present assumes "success"
5. Allow hooks for further processing - unchecking it just saves minimal processing power, as it won't try to execute filters
6. Map your form submission values (from the CF7 field tags) to expected fields for your service.  1:1 mapping given as the name of the CF7 field and the name of the 3rdparty field; you can also provide static values by checking the "Is Value?" checkbox and providing the value in the "CF7 Field" column.
7. Add, remove, and rearrange mapping - basically just for visual clarity.
8. Use the provided hooks (as given in the bottom of the service block)
9. Add new services as needed

= How can I pre/post process the request/results? =

See section [Hooks][].  See plugin folder `/3rd-parties` for example code for some common CRMs, which you can either directly include or copy to your code.

[Hooks]: /extend/plugins/contact-form-7-3rd-party-integration/other_notes#Hooks
[Screenshots]: /extend/plugins/contact-form-7-3rd-party-integration/screenshots

== Screenshots ==

1. Admin page - create multiple services, set up debugging/notice emails, example code
2. Sample service - mailchimp integration, with static and mapped values
3. Sample service - salesforce integration, with static and mapped values


== Changelog ==

= 1.3.3.1 =

* Added 'deprecation' notice, link to replacement: [Forms: 3rd-Party Integration](http://wordpress.org/plugins/forms-3rdparty-integration/)

= 1.3.3 =

* late include of `includes.php` breaks usage of `v()` during send -- including in _init_ action instead, so it's available everywhere (TODO: "smarter include" only when needed - i.e. admin_init and before_send?)
- debug email can fail due to FROM header: usage of `$_SERVER['HTTP_HOST']` in some enviroments results in "bad sender" by including www; stripping manually

= 1.3.2 =

* Added failure hook - if your service fails for some reason, you now have the opportunity to alter the CF7 object and prevent it from mailing.

= 1.3.1 =

* Added check for old version of CF7, so it plays nice with changes in newer version (using custom post type to store forms instead, lost original function for retrieval)
* see original error report http://wordpress.org/support/topic/plugin-contact-form-7-3rd-party-integration-undefined-function-wpcf7_contact_forms?replies=2#post-2646633

= 1.3.0 =
moved "external" includes (hidden-field plugin) to later hook to avoid conflicts when plugin already called

= 1.2.3 =
changed filter callback to operate on entire post set, changed name

= 1.2.2 =
fixed weird looping problem; removed some debugging code; added default service to test file

= 1.2 =
moved filter to include dynamic and static values; icons

= 1.1 =
added configuration options, multiple services

= 1.0 =
base version, just directly submits values


== Upgrade Notice ==

= 1.3.3.1 =
Nothing has changed in this version; it's just a helpful notice to let you know this plugin has been "replaced" by [Forms: 3rd-Party Integration][http://wordpress.org/plugins/forms-3rdparty-integration/], which integrates with Gravity Forms in addition to Contact Form 7.  Otherwise active development of this plugin has stopped.
Please note that migration will entail reconfiguring the plugin.

= 1.3.1 =
See 1.3.0 notice

= 1.3.0 =
Fixes should accomodate CF7 < v1.2 and changes to >= v1.2 -- please test and check when upgrading, and report any errors to the plugin forum.

== Hooks ==

1. `add_action('Cf73rdPartyIntegration_service_a#',...`
    * hook for each service, indicated by the `#` - _this is given in the 'Hooks' section of each service_
    * provide a function which takes `$response, &$results` as arguments
    * allows you to perform further processing on the service response, and directly alter the processing results, provided as `array('success'=>false, 'errors'=>false, 'attach'=>'', 'message' => '');`
        * *success* = `true` or `false` - change whether the service request is treated as "correct" or not
        * *errors* = an array of error messages to return to the form
        * *attach* = text to attach to the end of the email body
        * *message* = the message notification shown (from CF7 ajax response) below the form
    * note that the basic "success condition" may be augmented here by post processing
2. `add_filter('Cf73rdPartyIntegration_service_filter_post_#, ...`
    * hook for each service, indicated by the `#` - _this is given in the 'Hooks' section of each service_
    * allows you to programmatically alter the request parameters sent to the service
3.  `add_action('Cf73rdPartyIntegration_onfailure', 'mycf7_fail', 10, 3);`
    * hook to modify the CF7 object if service failure of any kind occurs -- use like:
        function mycf7_fail(&$cf7, $service, $response) {
            $cf7->skip_mail = true; // stop email from being sent
            // hijack message to notify user
            ///TODO: how to modify the "mail_sent" variable so the message isn't green?  on_sent_ok hack?
            $cf7->messages['mail_sent_ok'] = 'Could not complete mail request: ' . $response['safe_message']; 
        }
    * needs some way to alter the `mail_sent` return variable in CF7 to better indicate an error - no way currently to access it directly.

Basic examples provided for service hooks directly on plugin Admin page (collapsed box "Examples of callback hooks").  Code samples for common CRMS included in the `/3rd-parties` plugin folder.


== About AtlanticBT ==

From [About AtlanticBT][].

= Our Story =

> Atlantic Business Technologies, Inc. has been in existence since the relative infancy of the Internet.  Since March of 1998, Atlantic BT has become one of the largest and fastest growing web development companies in Raleigh, NC.  While our original business goal was to develop new software and systems for the medical and pharmaceutical industries, we quickly expanded into a business that provides fully customized, functional websites and Internet solutions to small, medium and larger national businesses.

> Our President, Jon Jordan, founded Atlantic BT on the philosophy that Internet solutions should be customized individually for each client's specialized needs.  Today we have expanded his vision to provide unique custom solutions to a growing account base of more than 600 clients.  We offer end-to-end solutions for all clients including professional business website design, e-commerce and programming solutions, business grade web hosting, web strategy and all facets of internet marketing.

= Who We Are =

> The Atlantic BT Team is made up of friendly and knowledgeable professionals in every department who, with their own unique talents, share a wealth of industry experience.  Because of this, Atlantic BT always has a specialist on hand to address each client's individual needs.  Due to the fact that the industry is constantly changing, all of our specialists continuously study the latest trends in all aspects of internet technology.   Thanks to our ongoing research in the web designing, programming, hosting and internet marketing fields, we are able to offer our clients the most recent and relevant ideas, suggestions and services.

[About AtlanticBT]: http://www.atlanticbt.com/company "The Company Atlantic BT"
[WP-Dev-Library]: http://wordpress.org/extend/plugins/wp-dev-library/ "Wordpress Developer Library Plugin"

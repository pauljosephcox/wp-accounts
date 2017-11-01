# WordPress Member Accounts
A simple wordpress user management system.



## Quick Intro
The Accounts plugin is built entirely on wordpress actions by parsing the url into segments and calling actions based on the url referenced from /account. This page is built into wordpress so that it can not be deleted. Any slug after /account will be run as a do_actions.

*For Example*
http://website.dev/account/create

```
// will run the following actions
do_action('accounts_before_content_create');
do_action('accounts_the_content_create');
do_action('accounts_after_content_create');
```

## Default Endpoints
------

#### /account/
This is the default dashboard or login screen.
Actions can be added to the dashboard using ```add_action('accounts_the_content_dashboard');```


#### /account/create/

This is the account creation page


#### /account/lostpassword

This is the password recovery page


#### /account/active

This is the activation screen for a new account



### Adding Custom Pages
To add a custom page simply add a new action

```
add_action('accounts_the_content_subscriptions','function');
```

The above would run a function with could include a template file when ever the url ```/account/subscription``` is hit.



## Adding Navigation Options
------
Add navigation options to the account manager using  ```add_filter('accounts_sections','function;);```

```
add_filter( 'accounts_sections', 'account_navigation',1);

functions account_navigation($sections){
	$sections[] = array('id' => 'details', 'href' => '/account/#details', 'text' => 'My Details');
	return $sections;
}

```



# Hooks & Filters
-----

#### Updating Members
@param $vars = $_POST varaiables

Runs when a member updates their details successfully or on failure.

``` $vars = apply_filters('accounts_edit_account_save_success',$var); ```

``` $vars = apply_filters('accounts_edit_account_save_fail',$var); ```

#### Create Member
Runs on new member creation

``` $user_id = apply_filters('accounts_create_account_success',$user_id); ```

``` $vars = apply_filters('accounts_create_account_fail',$var); ```

#### Activate Member
Runs when a member activates their account.

``` $vars = apply_filters('accounts_active_account_success',$vars); ```

``` $vars = apply_filters('accounts_active_account_fail',$var); ```

#### Request Lost Password
Runs when a member requests a password reset

``` $vars = apply_filters('accounts_request_lost_password',$vars); ```

#### Reset Password
Runs when a member resets their password

``` $vars = apply_filters('accounts_reset_account_password_success',$vars); ```

``` $vars = apply_filters('accounts_reset_account_password_fail',$vars); ```


#### Activation Email
Runs when the activation email is sent.

```$vars = apply_filters('accounts_activation_email_subject',$subject); ```

```$vars = apply_filters('accounts_activation_email_body',$subject); ```



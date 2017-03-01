# eve-jacknife
This Project is used to audit an api so that you might see your own skills and what ships you can fly, mails, contracts,assets, and any other given access from a specific api key
After inputting your api key either precreated or by using one of the two create links above you can use the site to view the previously mentioned items from the eve api as well as
checking if you have the skills required to fit a particular ship. This site is mainly for those who want to check another character's api to determine if they meet requirements for their
corp or if what they are telling them is true however this can also be useful to new players to see what ships they can and cannot fly effectively. Green links at the top of the page
can be used to navigate the apis or selecting one-page will display everything at once,WARNING may not load fully on first attempt


A fully function Demo can be seen at http://evejackknife.com/

If you would like to enable SSO then you need to add the following to your eve.config.php with client information values from https://developers.eveonline.com/

```
define("SSO_URL","login.eveonline.com");
define("SSO_CLIENTID", "*");
define("SSO_SECRET", "*");
define("SSO_CALLBACK", "*");
```

__Installation Requirements__


* Web server with php 5.6 or higher capability (apache,lighttpd,nginx with mod_php or php-fpm)

* Mysql or MariaDB


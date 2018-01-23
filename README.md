# Ceasing Development
It is with a heavy heart that I must cease development and announce that I will not be porting JackKnife to ESI. After many months of attempts I noticed that ESI just requires too many calls to be made to allow JackKnife to continue as is. After making multiple request to CCP to add bulk endpoints in the hope to get a worst case scenario of 2510 calls for transactions down to a more reasonable 10 I was told by their developers that I am too lazy and should just quit so someone else can do it "correctly". As such I am taking CCPs advice along with their horrible customer service and ending development. I am sorry for anyone who is using JackKnife and was hoping for the ESI port I promised but CCP has determined that they have a vision for their API and if you don't want to have many threads running 24/7 to keep your service up to date then its not an application they want. 
# Eve-Jacknife
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


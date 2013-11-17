symfony2 in apache
==================
1. always point to web directory

2. put this 2 line in production mode
parameters:
    router.options.matcher.cache_class: ~ # disable router cache
    router.options.matcher_class: Symfony\Component\Routing\Matcher\ApacheUrlMatcher

3. dev mode use localhost/app_dev.php/...

4. enable apc,
disable profiler toolbar if necessary,
change realpath_cache_size = 4096k in php.ini

5. the main reason is xdebug enabled

6. set: date.timezone = UTC

pass all checklist in windows
=============================
1. copy the php_apc.ini into php ext folder


generate entity from existing database
======================================

first remove enum because it is not support by doctrine

note: remember the path to src/company/bundle need to correct
because after generate, it will show the path, if path error, regenerate it


1. generate xml for doctrine (--em is entity manager (symfony or underlying))

s doctrine:mapping:import  --em=symbol --force JackImportBundle xml

s doctrine:mapping:import  --em=system --force JackImportBundle xml


** you need to create entity folder first

2. generate entity from xml using annotation (./src/jack/ directory to company)

s doctrine:mapping:convert --em=symbol annotation ./src --force

s doctrine:mapping:convert --em=system annotation ./src

if exist files, use --force


3.  generate getter and setter in php files

s doctrine:generate:entities JackImportBundle

remember to set default value at symbol (country = usa, marketcap = mega, shortable = 1)





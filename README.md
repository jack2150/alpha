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

s doctrine:mapping:convert --em=symbol annotation ./src

s doctrine:mapping:convert --em=system annotation ./src



3.  generate getter and setter in php files

s doctrine:generate:entities JackImportBundles






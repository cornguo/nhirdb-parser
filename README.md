# NHIRDB parse scripts

This is a set of PHP scripts which can parse DAT files
obtained from NHIRDB (http://nhird.nhri.org.tw/).


## Data Folder Structure

```
~/[TABLE_NAME]/[YEAR]/[FILENAME].DAT
```

Please note that the script detects year from file path,
that is, the path can only contain one folder named by year.

eg.

```
~/data/nhirdb/OO/2004 ....... OK
~/2014data/nhirdb/OO/2004 ... OK
~/2014/nhirdb/OO/2004 ....... this will cause errors
```

You can see an example data of OO table in example.


## Config File

TBD


## Usage

TBD

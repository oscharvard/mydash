mydash
======

This repo contains a working demonstration of the MyDASH usage reporting system for DSpace, developed by Reinhard Engels at Harvard Library's Office for Scholarly Communication.  The original installation is at https://osc.hul.harvard.edu/dash/mydash/.  The demo, contained in mydash-demo/, is a complete, bare-bones installation of Drupal 7 using SQLite.  The Drupal database is in the default location, sites/default/files/.ht.sqlite.  The MyDASH code and data are in sites/default/files/mydash/, except for a bit of PHP in the mydash page.  In order to stay under the 100M size limit, the simulated MyDASH data file is compressed; you'll need to

        $ cd mydash-demo/sites/default/files/mydash/data/
        $ tar xvzf mydash.sqlite.tgz

The demo runs as-is in MAMP; on a Debian system it required the installation of php5-sqlite and 

        # chown -R www-data.www-data mydash-demo/

The admin user is admin/changeme; each author in the database is also a Drupal user, with password 1234; log in as the admin to see the usernames.  When you log in as an author, you see your own articles, except in the cases specified in sites/default/files/mydash/inc/privs.json, where a few users are permitted to see detailed stats for a school or department.  The admin user can see everything. 

This repo will also contain the back-end scripts that generate mydash.sqlite -- stay tuned.

Although this system was built to run in Drupal, it's not a Drupal module (at the moment), and could run in some other environment, although the authentication system would have to be overhauled.  For that matter, it could be used to report usage data from an institutional repository other than DSpace.

This system stands on the shoulders of three excellent JavaScript libraries: [Highcharts](http://www.highcharts.com/), [amMap](http://www.ammap.com/), and [DataTables](https://datatables.net/).  All three are in sites/default/files/mydash/assets/, but you may wish to use current versions in your own development.
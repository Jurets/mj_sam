Description :
-------------

This plugin is a fork of  Moodle 2.3 plugin by Michaël Egi, https://moodle.org/plugins/view.php?plugin=local_eduweb_databasefiledownload that allow downloading as a zip file of files attached to a database activity in unsorted of sorted mode.

We added :

1) now runs under Moodle 2.4

2) the ability to also download picture fields in the zip

3) a french translation (by Luiggi Sansonetti)


Code was simplified and many PHP notices were removed.

Enjoy. 


Installation :
--------------

1) Using git (recommended)

Go to your moodle installation directory and clone the github repo by :

 cd /var/www/moodle
 
 git clone  https://github.com/patrickpollet/moodle_local_eduweb_databasefiledownload.git  local/eduweb_databasefiledownload
 
 echo 'local/eduweb_databasefiledownload' >> .git/info/exclude
 

 
2) using zip

Collect the zip file of the appropriate moodle_2x branch on github

Unzip it into the local directory of your moodle installation

Rename the folder patrickpollet-moodle_local_eduweb_databasefiledownload-xxxxxx to eduweb_databasefiledownload

3) finally visit as usual Site Administration/Notifications.


Usage :
-------

This plugin add a tab to the database view, and to items to the menu Database settings, if the target adatabase activity contains fields of type File and/or picture.

Somme screenhots area available on the wiki page https://github.com/patrickpollet/moodle_local_eduweb_databasefiledownload/wiki

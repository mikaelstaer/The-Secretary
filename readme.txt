***

THE SECRETARY
Simple content management.
Version 2.3 / February 24 2012
www.thesecretary.org

***

If you encounter any problems, are confused or have questions, visit www.thesecretary.org and make a post in the forums.

***

APPLICATION REQUIREMENTS

In order to use The Secretary, your host must support the following (if you are unsure, copy this list and send an email to your webhost's support):

- PHP 5.5+
- MySQL 5+
- Linux/Apache server
- Allow mod_rewrite for clean URLs (if you wish to use them)

You must use a modern web browser when using the application. Internet Explorer is not supported. Outdated browsers are not supported. Try any of 
the following:

- Firefox (www.getfirefox.com)
- Safari (www.apple.com/safari)
- Chrome (www.google.com/chrome)

***

UPGRADING INSTRUCTIONS 2.2.1 - 2.3 : READ CAREFULLY!

1. Backup "config.inc.php" in the "assistants" folder.

2. Backup your current theme. Do this by finding the theme's folder in ../site/themes and downloading a copy to your computer.

3. If you have any custom plugins or modules, make sure to back them up as well.

4. Delete all files and folders in your Secretary folder, EXCEPT FOR THE "FILES" FOLDER.

5. Upload all the files and folders from the 2.2 download, EXCEPT FOR THE "FILES" FOLDER, to your Secretary folder. Your folder structure should look like this:

- [your_secretary_folder]
	- files
	- site
	- system
	index.php
	install.php
	login.php
	logout.php

6. Upload your backup of "config.inc.php" to the system/assistants folder.

7. If you have been using a custom theme, upload your backup to the site/themes/ folder.

8. After upgrading, login and double check that the various upload settings are correct, by clicking under all settings panels in the Settings menu tab.

9. Enjoy!

***

INSTALLATION INSTRUCTIONS

Before you begin installing The Secretary double-check that your host meets the requirements. Second, you must have the connection information to your MySQL 
database (contact your webhost for this information if you do not have it). You will need the following information:

- database host
- database name
- database username
- database password

STEP 1

Connect to your server (website) with your FTP program. Create a new folder for The Secretary on your server.

For example, if you would like The Secretary to be in a folder called 'cms', create that folder and upload the files there. This is NOT the folder for your website - The Secretary is a separate application that manages the content on your website, and therefore should not be placed in the same folder. If you would like your website to be www.yourdomain.com then Secretary should reside in a sub-folder there.

STEP 2

Upload the contents of the Secretary download folder to this new folder.

STEP 3

Using your FTP program, set the "files" folder's (and all files and folders within it) permissions to 755. This is usually done by right clicking on the file name and choosing "Get Info" or "Properties", and editing what is called "File Permissions". Other FTP programs have an option to "Chmod" a file - this is the same function.

STEP 4

Now you are ready to complete the installation. Point your browser to [your site]/[secretary folder]/install.php and follow the instructions given.

If you uploaded the files to a folder called 'cms' in the root folder of your website, you would go to www.yourdomain.com/cms/install.php.

STEP 5

After a successful installation, a file named 'site.php' is created in the 'site' folder. Move this file to the folder where you would like your website to appear. DO NOT move it to the same folder as your Secretary installation. For example, if you have installed the application at www.yourdomain.com/cms/, do not move 'site.php' to the 'cms' folder. In most cases, you will want to move it to the root folder (www.yourdomain.com).

You may have to refresh the folder view in your FTP program to see the new file, if you have had it open during the entire installation process.

Rename the file to 'index.php'.

You should also delete the install.php file after installing.

STEP 6

Login and double check your upload settings. You can do this by clicking under all settings panels in the Settings menu, and veryifying that the Upload Paths and Upload URLs are correct (so that you can upload images, audio and video and other files!).

STEP 7

Enjoy!

***

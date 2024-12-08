
1. Download the Project from GitHub
Clone or download the project repository from GitHub to your local machine:

2. Upload the Database
Import the provided testwork66543.sql file into your local MySQL database.
You can do this through phpMyAdmin, or via the command line as follows:

`bash
mysql -u username -p testwork66543 < testwork66543.sql
`

Ensure the database is created with the name testwork66543 and all tables are imported correctly.

3. Update wp-config.php
Open the wp-config.php file in your project directory.
Update the following database credentials to match your local setup:

`php
define('DB_NAME', 'testwork66543');
define('DB_USER', 'root'); // Your database username
define('DB_PASSWORD', '');  // Your database password (leave empty if not set)
define('DB_HOST', 'localhost');
` 
4. Change the Site URL

In your local WordPress installation, you need to change the site URL to match your localhost setup.
You can do this via the wp_options table:

`sql
UPDATE wp_options SET option_value = 'http://localhost/your-site-folder' WHERE option_name = 'siteurl';
UPDATE wp_options SET option_value = 'http://localhost/your-site-folder' WHERE option_name = 'home';
`
5. Login as Admin
Open your local WordPress site by navigating to http://localhost/your-site-folder in your browser.
The login credentials will be provided separately via a .txt file.

6. Run Your WordPress Site
Your site should now be up and running on your local machine with all the necessary content from the database.
Check the front-end and back-end to make sure everything works properly.
7. Optional: Additional Steps
If there are additional plugins or themes that need to be activated, do so through the WordPress dashboard.

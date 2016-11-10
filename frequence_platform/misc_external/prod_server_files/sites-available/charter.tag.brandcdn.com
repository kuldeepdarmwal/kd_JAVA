# Place any notes or comments you have here
# It will make any customisation easier to understand in the weeks to come

# domain: domain1.com
# public: /home/demo/public_html/domain1.com/

<VirtualHost 50.56.183.131:80>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  charter.tag.brandcdn.com
  ServerAlias charter.tag.brandcdn.com

  <Directory />
        Options FollowSymLinks -Indexes
        AllowOverride FileInfo
  </Directory>
  <Directory /var/websites/platform.brandcdn.com/public/autoscript>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>  


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.html index.htm
   DocumentRoot /var/websites/platform.brandcdn.com/public/autoscript



  # Custom log file locations
  LogLevel warn
  ErrorLog  /var/websites/platform.brandcdn.com/log/error.log
  CustomLog /var/websites/platform.brandcdn.com/log/access.log combined

</VirtualHost>

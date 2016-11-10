# Place any notes or comments you have here
# It will make any customisation easier to understand in the weeks to come

# domain: domain1.com
# public: /home/demo/public_html/domain1.com/

<VirtualHost 50.56.183.131:80>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  vantagelocal.brandcdn.com
  ServerAlias *.brandcdn.com

  RewriteEngine on
  RewriteCond %{REQUEST_URI} !^/autoscript
  RewriteCond %{HTTP_HOST} vantagelocal.brandcdn.com$
  RewriteRule ^/(.*) http://frequence.brandcdn.com/$1

  <Directory />
        Options FollowSymLinks -Indexes
        AllowOverride FileInfo
  </Directory>
  <Directory /var/websites/platform.brandcdn.com/public>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.html index.htm
   DocumentRoot /var/websites/platform.brandcdn.com/public

  # Custom log file locations
  LogLevel warn
  ErrorLog  /var/websites/platform.brandcdn.com/log/error.log
  CustomLog /var/websites/platform.brandcdn.com/log/access.log combined

</VirtualHost>

<VirtualHost 50.56.183.131:443>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin admin@brandcdn.com
  ServerName vantagelocal.brandcdn.com
  ServerAlias *.brandcdn.com

  RewriteEngine on
  RewriteCond %{REQUEST_URI} !^/autoscript
  RewriteCond %{HTTP_HOST} vantagelocal.brandcdn.com$
  RewriteRule ^/(.*) https://frequence.brandcdn.com/$1
 
  <Directory />
        Options FollowSymLinks -Indexes
        AllowOverride FileInfo
  </Directory>
  <Directory /var/websites/platform.brandcdn.com/public>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.html index.htm
   DocumentRoot /var/websites/platform.brandcdn.com/public

  SSLEngine ON
  SSLProtocol All -SSLv2 -SSLv3

   SSLCertificateFile /var/websites/platform.brandcdn.com/ssl/brandcdn/brandcdn.com.crt
   SSLCertificateKeyFile /var/websites/platform.brandcdn.com/ssl/brandcdn/brandcdn.com.key
   SSLCertificateChainFile /var/websites/platform.brandcdn.com/ssl/brandcdn/intermediate.crt

  # Custom log file locations
  LogLevel warn
  ErrorLog  /var/websites/platform.brandcdn.com/log/error.log
  CustomLog /var/websites/platform.brandcdn.com/log/access.log combined

</VirtualHost>


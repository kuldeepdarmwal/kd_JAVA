# Place any notes or comments you have here
# It might make any customisation easier to understand in the weeks to come

# domain: domain1.com
# public: /var/review_servers/demo/public_html/domain1.com/

<VirtualHost *:80>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  review-2.vantagelocaldev.com
  ServerAlias *.review-2.vantagelocaldev.com

  <Directory />
        Options FollowSymLinks
        AllowOverride FileInfo
  </Directory>
  <Directory /var/review_servers/review-2/public/vantagelocal.com>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.htm index.html
   DocumentRoot /var/review_servers/review-2/public/vantagelocal.com



  # Custom log file locations
  LogLevel warn
  ErrorLog  /var/review_servers/review-2/public/log/error.log
  CustomLog /var/review_servers/review-2/public/log/access.log combined

</VirtualHost>

<VirtualHost *:443>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  review-2.vantagelocaldev.com
  ServerAlias *.review-2.vantagelocaldev.com
  # Add Request header required
#  RequestHeader set X_FORWARDED_PROTO 'https'

  <Directory />
        Options FollowSymLinks
        AllowOverride FileInfo
  </Directory>
 <Directory /var/review_servers/review-2/public/vantagelocal.com>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php
   DocumentRoot /var/review_servers/review-2/public/vantagelocal.com

  SSLEngine ON

   SSLCertificateFile /home/vladmin/ssl/crt/www.vantagelocalbranding.crt

   SSLCertificateKeyFile /home/vladmin/ssl/key/www.vantagelocalbranding.com.key


  # Custom log file locations
  LogLevel warn
  ErrorLog  /var/review_servers/review-2/public/log/error.log
  CustomLog /var/review_servers/review-2/public/log/access.log combined

</VirtualHost>


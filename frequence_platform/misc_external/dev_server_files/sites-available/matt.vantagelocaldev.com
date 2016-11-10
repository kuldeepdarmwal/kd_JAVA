# Place any notes or comments you have here
# It matt make any customisation easier to understand in the weeks to come

# domain: domain1.com
# public: /home/demo/public_html/domain1.com/

<VirtualHost *:80>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  matt.vantagelocaldev.com
  ServerAlias *.matt.vantagelocaldev.com

  <Directory />
        Options FollowSymLinks
        AllowOverride FileInfo
  </Directory>
  <Directory /home/dev_matt/public/vantagelocal.com>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.htm index.html
   DocumentRoot /home/dev_matt/public/vantagelocal.com



  # Custom log file locations
  LogLevel warn
  ErrorLog  /home/dev_matt/public/log/error.log
  CustomLog /home/dev_matt/public/log/access.log combined

</VirtualHost>

<VirtualHost *:443>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  matt.vantagelocaldev.com
  ServerAlias *.matt.vantagelocaldev.com
  # Add Request header required
#  RequestHeader set X_FORWARDED_PROTO 'https'

  <Directory />
        Options FollowSymLinks
        AllowOverride FileInfo
  </Directory>
 <Directory /home/dev_matt/public/vantagelocal.com>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php
   DocumentRoot /home/dev_matt/public/vantagelocal.com

  SSLEngine ON

   SSLCertificateFile /home/vladmin/ssl/crt/www.vantagelocalbranding.crt

   SSLCertificateKeyFile /home/vladmin/ssl/key/www.vantagelocalbranding.com.key


  # Custom log file locations
  LogLevel warn
  ErrorLog  /home/dev_matt/public/log/error.log
  CustomLog /home/dev_matt/public/log/access.log combined

</VirtualHost>


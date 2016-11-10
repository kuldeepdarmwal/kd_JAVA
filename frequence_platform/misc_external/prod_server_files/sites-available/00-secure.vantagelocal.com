# Place any notes or comments you have here
# It will make any customisation easier to understand in the weeks to come

# domain: domain1.com
# public: /home/demo/public_html/domain1.com/

<VirtualHost 108.166.64.71:80>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  secure.vantagelocal.com
  ServerAlias secure.vantagelocal.com

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

<VirtualHost 108.166.64.71:443>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  secure.vantagelocal.com
  ServerAlias secure.vantagelocal.com

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

  SSLCertificateFile /var/websites/platform.brandcdn.com/ssl/crt/secure.vantagelocal.com.crt.2014-05-15
  SSLCertificateKeyFile /var/websites/platform.brandcdn.com/ssl/key/secure.vantagelocal.com.key.2014-05-15
  SSLCertificateChainFile /var/websites/platform.brandcdn.com/ssl/crt/secure.vantagelocal.com.intermediate.crt.2014-05-15

  # Custom log file locations
  LogLevel warn
  ErrorLog  /var/websites/platform.brandcdn.com/log/error.log
  CustomLog /var/websites/platform.brandcdn.com/log/access.log combined

</VirtualHost>


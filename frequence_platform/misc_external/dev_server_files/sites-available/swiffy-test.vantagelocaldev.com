# Place any notes or comments you have here
# It scott make any customisation easier to understand in the weeks to come

# domain: domain1.com
# public: /home/demo/public_html/domain1.com/

<VirtualHost *:80>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  swiffy-test.vantagelocaldev.com

  <Directory />
        Options FollowSymLinks
        AllowOverride FileInfo
  </Directory>
  <Directory /home/dev_scott/swiffy-test>/public
        Options Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.htm index.html
   DocumentRoot /home/dev_scott/swiffy-test/public



  # Custom log file locations
  LogLevel warn
  ErrorLog  /home/dev_scott/swiffy-test/log/error.log
  CustomLog /home/dev_scott/swiffy-test/log/access.log combined

</VirtualHost>



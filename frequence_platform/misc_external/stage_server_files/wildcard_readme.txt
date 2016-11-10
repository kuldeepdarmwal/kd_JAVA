# apache2 brandcdn readme BY WILL #

apache files are read in alpabetical order.  Sub domains that need to be loaded before any wildcards need to have a file name in higher alphabetical order.

wildcard.brandcdn.com has the wildcard in it.  Name any apache2 config files something higher up the alphabet than wildcard so they will be evaluated first.

ports.conf was modified to read requests on port 443 via their name and not the ip address.  This allows multiple ssl certificates to be valid for the same ip address but on different domains.

There are three files in wildcard.brandcdn.com related to ssl.
	  The public key(.crt)
	  The private key(.key)
	  and The intermediate key provided by Rapid SSL(.crt)
These are all necessary to the function of any brand cdn subdomains that want to use SSL.

ABOUT

Little Software Stats is the first free and open source application that allows software developers to monitor how their software is being used. It is developed using PHP & MySQL which allows it to be ran on most web servers. 

SYSTEM REQUIREMENTS

Little Software Stats is designed to run on PHP v5.2.x and MySQL v5.x.x alongside a web server such as Apache, Lighttpd, or Nginx. PHP needs specific extensions (which usually come with it) to be installed including MySQL/MySQLi, GD, JSON, SimpleXML, Hash, Session, and Zlib. Although it’s not required, Little Software Stats can be used with a URL rewriter module (such as mod_rewrite) which we have included several examples for various web servers on how to configure it for use with Little Software Stats. The appropriate web server and memory needed will depend on the scale of your software. For example, if your using Little Software Stats with a highly used software program then you may want to consider running Nginx or Lighttpd with lots of RAM. You should consider using something like suPHP which runs PHP at the user-level instead of the root user or the default PHP user.

INSTALLATION

Follow these steps to install Little Software Stats:

1. Upload and extract Little Software Stats to your web server
2. Create a MySQL database with a user that has full privileges to access and modify it
3. Go to http://example.com/install/ and follow the steps
4. Integrate Little Software Stats with your software and track your users

LICENSE

Little Software Stats itself is licensed under the GNU General Public License v3 and the Little Software Stats libraries are licensed under the GNU Lesser General Public License. 

WEBSITE

For more information, please visit http://www.little-software-stats.com
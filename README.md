# FreeRADIUS3 Genie
Sonar's FreeRADIUS3 Genie is a php application to assist with the setup and configuration of FreeRADIUS 3.x using the binaries released by NetworkRadius for use with Sonar

## Getting started

The freeradius3-genie/setup script is designed to be run on a clean installation of [Ubuntu 18.04 or 20.04](http://www.ubuntu.com/download/server).

The setup script will try and get you up and running quickly but manual steps are show below incase setup encounters an error and fails.

It will configure the server for use with the freeradius binary packages provided by networkradius.com, install mariadb, and php-cli.
setup will also create a swap space on the server if required.

You will need to be root in order to run the installation. Use `sudo -i` to become root and load root's environment. Then go to the directory you wish to set up FreeRADIUS 3 Genie on, and run:

```bash
git clone https://github.com/theglossy1/freeradius3-genie
cd freeradius3-genie
./setup
```

`setup` will perform various operation to create a good environment for FreeRADIUS to run. Toward the end of the installation, it will ask a few questions.

If errors occur, please contact Sonar support.

---------------

manual installation steps if not using the provided setup script , some steps are included to aid with troubleshooting.

!!! if you are following the manual steps due to a setup scrpt failure, please run the checks included below to help determine the cause of failure.

run the following commands to prepare the installation on your server based on a clean server.


###  Ubuntu 18.04

add the networkradius repo and import the gpg key and update the local apt database.

1. ` sudo echo 'deb https://packages.networkradius.com/releases/ubuntu-bionic bionic main' >> /etc/apt/sources.list `
2. ` sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-key 0x41382202 `
3. ` sudo apt-get update `

to check your apt sources.list for the presence of the networkradius repo and the apt gpg key.

4. ` sudo cat /etc/apt/sources.list | grep networkradius `
5. ` sudo apt-key list | grep networkradius `

upgrade system base packages

6. ` sudo apt-get upgrade `

install php

8. ` sudo apt-get install php-cli php-mbstring php-mysql `

Once these commands are complete, you can install your preffered supported database server MySQL or MariaDB, and the FreeRADIUS server itself.
Run the following commands to complete these steps MariaDB is used as the default by the setup script and in the manual steps here.

Make sure to check that the system will use the networkradius binaries using apt-cache policy

1. ` sudo apt-get install mariadb-server mariadb-client `
2. ` sudo apt-cache policy freeradius `
3. ` sudo apt-get install freeradius freeradius-common freeradius-utils freeradius-mysql `

Once all packages are installed, you can download FreeRADIUS3 Genie, those who are just troubleshooting can skip this !

Run these commands from the root of your home directory.

1. ` cd ~ `
2. ` git clone https://github.com/ellisway/freeradius3-genie.git `
3. ` chmod 755 ~/freeradius3-genie/setup `

### A note on hosting

If you're hosting this online, it's likely that your server does not have any swap memory setup. If you've selected a server with a low amount of RAM (1-2G), or even if you've picked more, it can be worthwhile setting up a swap partition to make sure you don't run into any out of memory errors.

Your swap file size should be, at minimum, equal to the amount of physical RAM on the server. It should be, at maximum, equal to 2x the amount of physical RAM on the server. A good rule of thumb is to just start by making it equal to the amount of available RAM, increasing to double the RAM if you run into out of memory errors.

If you run into out of memory errors after moving to 2x the amount of RAM, you should increase the amount of RAM on your server rather than increasing swap. The [SwapFaq](https://help.ubuntu.com/community/SwapFaq) on ubuntu.com can be helpful as well.

To setup swap, run the following commands as root (or by putting 'sudo' in front of each command):

1. `/usr/bin/fallocate -l 4G /swapfile` where 4G is equal to the size of the swap file in gigabytes.
2. `/bin/chmod 600 /swapfile`
3. `/sbin/mkswap /swapfile`
4. `/sbin/swapon /swapfile`
5. `echo "/swapfile   none    swap    sw    0   0" >> /etc/fstab`
6. `/sbin/sysctl vm.swappiness=10`
7. `echo "vm.swappiness=10" >> /etc/sysctl.conf`
8. `/sbin/sysctl vm.vfs_cache_pressure=50`
9. `echo "vm.vfs_cache_pressure=50" >> /etc/sysctl.conf`

## Genie

**Genie** is a command php application built to help automate the setup and configuration of your FreeRADIUS server. We're going to step through each initial setup item to get our initial configuration out of the way. Type `./genie` and you'll see something like this:

![Image of Genie](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/genie.png)

This is the tool you'll use to do **all** of your configuration - no need to jump into configuration files or the MySQL database (but you can if you want)!

### First steps

Let's start by getting the database setup. Highlight the **Initial Configuration** option, press the space bar to select it, and then press enter. You'll see an option titled **Setup initial database structure** - press the space bar to select it, press enter, and your database will be configured. If you
receive an error message about credentials, double check the root password you placed into your `.env` file in the **Configuration** section.

Once that's completed, we need to setup the FreeRADIUS configuration files. Select **Perform initial FreeRADIUS configuration** by using the space bar to select it, and then pressing enter. This will configure your FreeRADIUS server to use the SQL server as a backend, and restart it. It will automatically detect the version of FreeRADIUS you're using (2.x.x or 3.x.x) and copy the appropriate flavour of configuration to the /etc/freeradius directory.

### Managing your NAS

NAS stands for [Network Access Server](https://en.wikipedia.org/wiki/Network_access_server) - this is the device that you will be connecting to your RADIUS server to manage your clients. Typically, in an ISP network where the NAS is used to manage individual clients, the NAS
will be something like a PPPoE concentrator. Let's step through adding a new NAS to the FreeRADIUS server using Genie, and then configuring our NAS (a MikroTik router) to use the FreeRADIUS server.

In Genie (remember, to bring up Genie, just type `php genie`) make sure you're at the top level, and then select **NAS Configuration** followed by **Add NAS**. You will be asked for the IP address of the client, and to enter a short name for it.

![Image of Genie](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/adding_nas.png)

The tool will then return a random secret to you - **copy this, as you will need to enter it into the PPPoE concentrator!**

We can now add this RADIUS server to our MikroTik to use it to manage our PPPoE sessions. This step will differ depending on your NAS manufacturer - refer to the manual if you're unsure. Jump into your MikroTik using [WinBox](http://www.mikrotik.com/download).

![Add RADIUS to MikroTik](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/add_radius_to_mikrotik.png)

Click **RADIUS** on the left, click the **+** button in the window that appears, and then fill in the following fields:

1. Check the **PPP** checkbox.
2. Enter the IP address of your RADIUS server in the **Address** field.
3. Enter the random secret Genie provided you with in the **Secret** field.
4. Under **Src. Address**, enter the IP that you entered into Genie when you created the NAS.

OK, your MikroTik is now setup to use RADIUS for PPP! We'll get into some deeper configuration later on.

You can also view all the NAS you've setup in your RADIUS server by selecting the **List NAS Entries** in Genie, and you can remove a NAS by using the **Remove NAS** option.

### Configuring MySQL for remote access

We also need to configure the MySQL server to allow remote access from Sonar, so that Sonar can write and read records for the RADIUS server. Let's do that now. Navigate into the **MySQL remote access configuration** menu, and select **Enable remote access**.

![Enabling remote access](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/enable_remote_access.png)

This makes the MySQL server listen for connections on all interfaces on the server, rather than just to localhost (127.0.0.1). Now we need to setup a remote user account, so that your Sonar instance can access the database. To do this, select **Add a remote access user** in the same menu.

Genie will ask you for the IP address of the remote server. If you don't know the IP of your Sonar instance, you can ping it to get the IP:

![Ping](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/ping.png)

Once you add the remote access user, Genie will give you back a random username and password. Copy this down - we'll need it in a minute!

![Adding a MySQL user](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/add_mysql_user.png)

If you ever need to add a new user, view the existing users, or remove a user, you can also do that in this menu.

### Linking your FreeRADIUS server to Sonar

Once this configuration is done, we need to add the RADIUS server into Sonar. Inside your Sonar instance, enter the **Network** navigation menu entry and click **RADIUS Server**.

![Configuring Sonar](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/sonar_config.png)

Enter all the information you have - the **Database Name** is *radius* and the **Database Port** is *3306*. Once the information is entered, click the **Validate Credentials** button at the top and you should see **Current Server Status** show *Accessible*.

## Basic PPPoE configuration

Once this is done, you'll have a basic setup in place to enable PPPoE. Here's a quick tutorial on setting up a simple PPPoE configuration on a MikroTik router.

First, we need to setup our IP pools. These should correspond to IP pools you have created in your Sonar IPAM - refer to the Sonar documentation for details on this! To configure pools, navigate to **IP > Pool** in your MikroTik. You can create
as many IP pools here as you need, and chain them together so that if one pool is full, the next one is used. You can statically assign IPs to users from within Sonar by associating an IP with their RADIUS account. If you don't do this, then an IP will
be selected from an available pool when the client connects, and Sonar will dynamically learn that IP and enter it as a soft assignment inside Sonar.

![IP Pool](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/pool.png)

The pool configuration is pretty simple - a start IP, an end IP, and the next pool to use if this one is full.

Once you've configured your pools, click **PPP** in the menu on the left and then click the **Profiles** tab. Click the **+** button to create a new profile.

We're going to configure a very basic profile. Enter a name, select a local address to use for the profile (in this example, I used the first IP in the subnet for my pool - note that this IP is *not* included in my pool range!) and for remote address, select your first pool.
Enter some DNS servers to assign to users, and under the **Limits** tab, set a session timeout. This will disconnect users after a certain period of time and they will have to reconnect. If you want to allow infinite sessions, don't set a timeout. Something like 24 hours is a reasonable
setting if you want to have a timeout value.

![PPP Profile](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/ppp.png)

Once your profile is configured, click the **Secrets** tab, and click the **PPP Authentication&Accounting button**.

![AAA](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/aaa.png)

Make sure *Use Radius* is checked, and that *Accounting* is checked. Make sure *Interim Update* is set to a reasonable value in minutes. This is how frequently this MikroTik will send accounting data to your RADIUS server. If you make this too short, and you have a lot of clients, your server will become overloaded.
There is no hard and fast rule as to what to use here. The shorter the time, the more often accounting data will be sent to the RADIUS server, and the more frequently you'll see updates as to users data usage in Sonar. If you have a very small network (a few hundred users) you can probably set this to a low value (1-5 minutes) without
much impact. For larger networks, set this to at least 15 minutes - you may need to increase it even more for very large networks!

Now click the **PPPoE Servers** tab, and click the **+** button to create a server.

![PPPoE Server](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/pppoeserver.png)

Enter a name for the server, select the interface that your clients will be connecting on, and select the profile we created earlier. If you only want to allow one PPPoE session per host (which you probably do!) check *One Session Per Host*. Make sure all the authentication options at the bottom are checked.

You now have a very basic, functioning PPPoE server. Login to your Sonar instance, navigate to a user account, and access the **Network** tab, and then the **RADIUS** tab. Create a new RADIUS account and note the username and password.

Now, back in the MikroTik, Click the **Active Connections** tab and try connecting using a PPPoE client, authenticating using the credentials you just created in Sonar. You should be assigned an IP from the pool, and the connection will show up in the list! To assign a static IP, navigate back into Sonar,
go to the **Network** tab on an account, and then **IP Assignments**. Assign an IP to the RADIUS account, and then disconnect and reconnect your PPPoE client. You will be assigned the static IP you selected.

### Scaling FreeRADIUS to large networks

The FreeRADIUS [guide to scaling is pretty simple.](http://freeradius.org/features/scalability.html) The short version is, give it lots of RAM, CPU, fast disks, and tweak the couple of settings mentioned in the [scalability guide.](http://freeradius.org/features/scalability.html) If you're running a big network with hundreds of thousands and subscribers, and you want some help, let us know!

## Further security

It's possible to further secure your FreeRADIUS installation with a couple of steps, detailed below. These steps are not required, but are recommended.

### Configuring the connectivity between Sonar and FreeRADIUS to use TLS

Configuring Sonar to use TLS to connect to the SQL server backing FreeRADIUS will ensure all data transferred is encrypted. The overhead is minimal, it just requires some effort to do the initial setup and make the necessary changes in Sonar. This guide assumes you have followed the steps above, you're using Ubuntu 16.04 and MariaDB.

In order for SSL connectivity to work, your RADIUS server must be entered into Sonar with a hostname (e.g. **radius.sonar.software**) and not an IP address. The certificate we generate below **must** match the hostname exactly.

First, we're going to make a folder to store our certs in.

`mkdir /etc/mysql/certs`

Now we're going to create the certificates. Run the commands below.

`openssl genrsa 2048 > /etc/mysql/certs/ca-key.pem`

`openssl req -sha1 -new -x509 -nodes -days 10000 -key /etc/mysql/certs/ca-key.pem > /etc/mysql/certs/ca-cert.pem`

When running the second command, you will be prompted to enter some variables. Just fill in some reasonable data here, but make sure the **Common Name** field is **NOT** the exact hostname of your RADIUS server. Set it to something different, for example **SonarRadius**.

`openssl req -sha1 -newkey rsa:2048 -days 10000 -nodes -keyout /etc/mysql/certs/server-key.pem > /etc/mysql/certs/server-req.pem`

You will again be prompted to enter variables here. Enter the same data as you entered previously, but this time make sure the **Common Name** field is the exact hostname of your RADIUS server. When prompted for a challenge password, just press enter.

`openssl x509 -sha1 -req -in /etc/mysql/certs/server-req.pem -days 10000 -CA /etc/mysql/certs/ca-cert.pem -CAkey /etc/mysql/certs/ca-key.pem -set_serial 01 > /etc/mysql/certs/server-cert.pem`

`openssl rsa -in /etc/mysql/certs/server-key.pem -out /etc/mysql/certs/server-key.pem`

`openssl req -sha1 -newkey rsa:2048 -days 10000 -nodes -keyout /etc/mysql/certs/client-key.pem > /etc/mysql/certs/client-req.pem`

Again, fill in the variables. This time, set **Common Name** to **Sonar**, and leave the challenge password blank.

`openssl x509 -sha1 -req -in /etc/mysql/certs/client-req.pem -days 10000 -CA /etc/mysql/certs/ca-cert.pem -CAkey /etc/mysql/certs/ca-key.pem -set_serial 01 > /etc/mysql/certs/client-cert.pem`

`openssl rsa -in /etc/mysql/certs/client-key.pem -out /etc/mysql/certs/client-key.pem`

You now have all the certificates generated that we'll need to enable TLS connectivity. We now need to reconfigure MariaDB to use these certificates.

`nano /etc/mysql/mariadb.conf.d/50-server.cnf`

Once inside the configuration file, add these lines to the end of the file:

`ssl-ca=/etc/mysql/certs/ca-cert.pem`

`ssl-cert=/etc/mysql/certs/server-cert.pem`

`ssl-key=/etc/mysql/certs/server-key.pem`

Now restart MariaDB:

`service mysql restart`

You can verify if SSL is now enabled by doing the following:

`mysql -uroot -p<YOUR ROOT MYSQL PASSWORD HERE>`

Once in the MySQL command line, do `show global variables like 'have_ssl';` and you should see `have_ssl` with a value of `YES`. If you do not, go back through the preceeding steps and redo all steps until the value becomes `YES`.

You now need to transfer the client files and the ca-cert.pem file from this server to your Sonar instance. A quick and easy way to do this is to use [FileZilla](https://filezilla-project.org/) to connect via SFTP, and then download the files. You will need `client-key.pem`, `client-cert.pem`, and `ca-cert.pem`.

Navigate to the Sonar RADIUS configuration page at **Network > Provisioning > RADIUS Server**. Configure your RADIUS server here, and save. After that's done, check the checkbox labelled **Enable SSL connectivity** and upload the client key, client certificate, and CA certificate from your RADIUS server. Click **Save** and then click **Validate Credentials** at the top of the page. If the credential validation now fails (and it was working previously) then you have not performed a step here properly - go back to the beginning and start over. The most common cause of the error is not entering the proper values in the **Common Name** fields when generating the certificates, or misconfiguring the MySQL server.

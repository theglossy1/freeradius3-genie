# FreeRADIUS 3 Genie
Sonar's FreeRADIUS 3 Genie is a php application to assist with the setup and configuration of FreeRADIUS 3.x using the binaries released by NetworkRadius for use with Sonar.

FreeRADIUS 3 Genie is geared toward Sonar version 2. It can be used with Sonar v1, but these instructions are for v2.

## Getting started

FreeRADIUS 3 Genie is designed to be run on a clean installation of [Ubuntu 18.04 or 20.04](http://www.ubuntu.com/download/server).

The setup script will configure the server for use with the FreeRADIUS binary packages provided by networkradius.com, MariaDB, and the necessary php components. It will also set up virtual memory swap on the server if required.

You will need to be root in order to run the installation. Use `sudo -i` to become root and load root's environment. Then go to the directory you wish to set up FreeRADIUS 3 Genie on, and run:

```bash
git clone https://github.com/theglossy1/freeradius3-genie
cd freeradius3-genie
./setup
```

`setup` will perform various operation to create a good environment for FreeRADIUS 3 to run. Toward the end of the installation, it will ask a few questions.

If errors occur, please contact your Sonar Client Experience Manager or [Sonar support](https://docs.sonar.expert/working-with-the-sonar-team-additional-resources/best-practices-for-fast-tracking-a-support-request).

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

**Genie** is a command php application built to help automate the setup and configuration of your FreeRADIUS 3 server. We're going to step through each initial setup item to get our initial configuration out of the way. Type `./genie` and you'll see something like this:

![Image of Genie](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/genie.png)

This is the tool you'll use to do **all** of your configuration - no need to jump into configuration files or the MySQL database (but you can if you want)!

### First steps

Let's start by getting the database setup. Highlight the **Initial Configuration** option, press the space bar to select it, and then press enter. You'll see an option titled **Setup initial database structure** - press the space bar to select it, press enter, and your database will be configured. If you
receive an error message about credentials, double check the root password you placed into your `.env` file in the **Configuration** section.

Once that's completed, we need to setup the FreeRADIUS configuration files. Select **Perform initial FreeRADIUS configuration** by using the space bar to select it, and then pressing enter. This will configure your FreeRADIUS server to use the SQL server as a backend, and restart it.

### Managing your NAS

NAS stands for [Network Access Server](https://en.wikipedia.org/wiki/Network_access_server) - this is the device that you will be connecting to your RADIUS server to manage your clients. Typically, in an ISP network where the NAS is used to manage individual clients, the NAS will be something like a PPPoE concentrator. Let's step through adding a new NAS to the FreeRADIUS server using Genie, and then configuring our NAS (a MikroTik router) to use the FreeRADIUS server.

In Genie (remember, to bring up Genie, just type `./genie` from its directory) make sure you're at the top level, and then select **NAS Configuration** followed by **Add NAS**. You will be asked for the IP address of the client, and to enter a short name for it.

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

Genie will ask you for the IP address of the remote server. You will need to put in the Sonar *egrees* IP address. For instructions on finding that address, check the  [Sonar IP Addressing Knowledgebase article](https://docs.sonar.expert/networking/sonar-ip-addressing).

Once you add the remote access user, Genie will give you back a random username and password. Copy this down - we'll need it in a minute!

![Adding a MySQL user](https://github.com/SonarSoftware/freeradius_genie/blob/master/images/add_mysql_user.png)

If you ever need to add a new user, view the existing users, or remove a user, you can also do that in this menu.

### Next steps

For the rest of the instructions regarding how to integrate with Sonar, please see our knowledge base article [RADIUS Integration with Sonar: Linking your FreeRADIUS server to Sonar](https://docs.sonar.expert/networking/radius-integration-with-sonar)
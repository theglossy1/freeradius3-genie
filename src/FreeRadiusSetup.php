<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;
use RuntimeException;

class FreeRadiusSetup
{
    private $climate;
    public function __construct()
    {
        $this->climate = new CLImate;
    }

    /**
     * Configure the FreeRADIUS configuration files
     * for the networkradius.com binary packages
     */
    public function configureFreeRadiusToUseSql()
    {
        $mysqlPassword = getenv("MYSQL_PASSWORD");

        $v = `dpkg -s freeradius | grep Version`;
        $v = explode(" ",$v);
        $c = "/../conf/" . substr($v[1],0,1) . ".0/";
        $r = `apt-cache policy freeradius | grep networkradius`;
        switch ($c) {

        case "/../conf/3.0/":

            $this->climate->info("Detected FreeRADIUS version 3.x.x");
            $this->climate->lightBlue()->inline("Configuring FreeRADIUS to use the SQL database... ");
            try {
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "radiusd.conf /etc/freeradius/");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "mods-config/files/authorize /etc/freeradius/mods-config/files/");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "sql /etc/freeradius/mods-available");
                CommandExecutor::executeCommand("/bin/ln -fs /etc/freeradius/mods-available/sql -t /etc/freeradius/mods-enabled/");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "detail_coa /etc/freeradius/mods-available");
                CommandExecutor::executeCommand("/bin/ln -fs /etc/freeradius/mods-available/detail_coa -t /etc/freeradius/mods-enabled/");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "default /etc/freeradius/sites-available/");
                CommandExecutor::executeCommand("/bin/ln -fs /etc/freeradius/sites-available/default -t /etc/freeradius/sites-enabled/");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "inner-tunnel /etc/freeradius/sites-available/");
                CommandExecutor::executeCommand("/bin/ln -fs /etc/freeradius/sites-available/inner-tunnel -t /etc/freeradius/sites-enabled/");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "coa-relay /etc/freeradius/sites-available/");
                CommandExecutor::executeCommand("/bin/ln -fs /etc/freeradius/sites-available/coa-relay -t /etc/freeradius/sites-enabled/");
                CommandExecutor::executeCommand("/bin/mkdir --parents /etc/freeradius/sites-config/coa-relay/homeservers/");
                /* 
                * this is used for initial deployment of the coa relay 
                *
                * we need to change the ip address to be a numeric value inside the localhost-coa.conf example that is created from the template 
                * otherwise freeradius will not be able to start
                */
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "sites-config/coa-relay/homeservers/homeserver-template.conf /etc/freeradius/sites-config/coa-relay/homeservers/localhost-coa.conf");
                CommandExecutor::executeCommand("/bin/sed -i 's/ipaddr = nas-ip-address/ipaddr = 127.0.0.1/g' /etc/freeradius/sites-config/coa-relay/homeservers/localhost-coa.conf");
                CommandExecutor::executeCommand("/bin/sed -i 's/home_server coa-nas-short-name/home_server localhost-coa/g' /etc/freeradius/sites-config/coa-relay/homeservers/localhost-coa.conf");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "sites-config/coa-relay/coa-pool-template.conf /etc/freeradius/sites-config/coa-relay/pool.conf");
                CommandExecutor::executeCommand("/bin/sed -i 's/home_server = .*/home_server = localhost-coa/g' /etc/freeradius/sites-config/coa-relay/pool.conf");
                /*
                * generation of the home server config files is now handled by the genie nasmanagement 
                */
                CommandExecutor::executeCommand("/bin/sed -i 's/password = \"radpass\"/password = \"$mysqlPassword\"/g' /etc/freeradius/mods-enabled/sql");
                CommandExecutor::executeCommand("/usr/sbin/service freeradius restart");
            }
            catch (RuntimeException $e)
            {
                $this->climate->shout("FAILED!");
                $this->climate->shout($e->getMessage());
                $this->climate->shout("See /tmp/_genie_output for failure details.");
                return;
            }

            $this->climate->info("SUCCESS!");
            break;


        case "/../conf/2.0/":

            $this->climate->info("Detected Unsupported FreeRADIUS version 2.x.x things could be broken!");
            
            $this->climate->lightBlue()->inline("Configuring FreeRADIUS to use the SQL database... ");
            try {
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "radiusd.conf /etc/freeradius/");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "sql.conf/ /etc/freeradius/");
                CommandExecutor::executeCommand("/bin/cp " . __DIR__ . $c . "default /etc/freeradius/sites-available/");
                CommandExecutor::executeCommand("/bin/sed -i 's/password = \"radpass\"/password = \"$mysqlPassword\"/g' /etc/freeradius/sql.conf");
                CommandExecutor::executeCommand("/usr/sbin/service freeradius restart");
            }
            catch (RuntimeException $e)
            {
                $this->climate->shout("FAILED!");
                $this->climate->shout($e->getMessage());
                $this->climate->shout("See /tmp/_genie_output for failure details.");
                return;
            }
            break;

       }
    }
}

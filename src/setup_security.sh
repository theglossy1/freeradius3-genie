#!/bin/bash

# This is the automated Sonar version of mysql_secure_installation

mysql_command=$(which mysql)

do_query() {
    echo "$1" | $mysql_command -uroot $2
    return $?
}

read_password() {
    unset PASSWORD
    unset CHARCOUNT

    stty -echo

    CHARCOUNT=0
    while IFS= read -p "$PROMPT" -r -s -n 1 CHAR
    do
        # Enter - accept password
        if [[ $CHAR == $'\0' ]] ; then
            break
        fi
        # Backspace
        if [ $CHAR = $'\177' -o $CHAR = $'\010' ]; then
            if [ $CHARCOUNT -gt 0 ] ; then
                CHARCOUNT=$((CHARCOUNT-1))
                PROMPT=$'\b \b'
                PASSWORD="${PASSWORD%?}"
            else
                PROMPT=''
            fi
        else
            CHARCOUNT=$((CHARCOUNT+1))
            PROMPT='*'
            PASSWORD+="$CHAR"
        fi
    done

    stty echo
    echo $PASSWORD
}

# Check if there is a current password for MySQL root user
mysqladmin version &>/dev/null
if [ $? != 0 ]; then
    echo -n "There's already a password for MySQL. Enter it here to proceed: "
    PASS1=$(read_password)
    echo
    mysqladmin -p$PASS1 version &>/dev/null
    if [ $? != 0 ]; then
        echo -e "\nSorry, that's not it"
        exit 1
    fi
    CREDS=" -p$PASS1"
fi

while true; do
    echo -n "Enter new MySQL root password: "
    PASS1=$(read_password)
    echo -en "\nRe-enter new MySQL root password: "
    PASS2=$(read_password)
    echo

    if [ _$PASS1 = _$PASS2 ]; then
        break
    else
        echo "Passwords did not match; try again"
    fi
done

echo -----
echo Updating .env file
echo MYSQL_PASSWORD=$PASS1>.env
echo "Removing root remote login"
do_query "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" $CREDS
if [ $? != 0 ]; then
    echo ERROR!
    exit 1
fi

echo "Removing anonymous users"
do_query "DELETE FROM mysql.user WHERE User='';" $CREDS
if [ $? != 0 ]; then
    echo ERROR!
    exit 1
fi

echo "Removing test database"
do_query "DROP DATABASE IF EXISTS test;" $CREDS
if [ $? != 0 ]; then
    echo ERROR!
    exit 1
fi

echo "Updating root password"
do_query "UPDATE mysql.user SET Password=PASSWORD('$PASS1') WHERE User='root';" $CREDS
if [ $? != 0 ]; then
    echo ERROR!
    exit 1
fi

echo "Reloading privileges"
do_query "FLUSH PRIVILEGES;" $CREDS
if [ $? != 0 ]; then
    echo ERROR!
    exit 1
fi


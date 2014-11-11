/**
 * Parse the parameters sent in by `facter_databases`
 * to then call mysql::db
 */
define mysqld::dbparse() {

  $db = split($name, ":")
  $dbname = $db[0]
  $dbpassword = $db[1]

  mysqld::db { "${dbname}": password => "$dbpassword" }

}
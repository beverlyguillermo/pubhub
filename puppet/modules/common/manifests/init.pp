/**
 * Tasks shared by all server instances
 */

class common {

	exec { "set timezone":
	  command => "/bin/ln -sf /usr/share/zoneinfo/America/New_York /etc/localtime",
	  refreshonly => true
	}

	exec { "add vendor/bin to path":
	  command => "echo 'export PATH=\$PATH:/var/www/html/jhu.edu/vendor/bin' >> /home/vagrant/.bashrc"
	}

}
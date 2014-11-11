class yum {

	yumrepo { 'shib':
	    baseurl  => "http://download.opensuse.org/repositories/security://shibboleth/CentOS_CentOS-6",
	    descr    => "Shibboleth Repo",
	    enabled  => 1,
	    gpgcheck => 0,
	    before   => Yumrepo['epel'],
	}

	yumrepo { 'epel': 
	    baseurl  => "http://dl.fedoraproject.org/pub/epel/6/x86_64",
	    descr    => "Extra Packages for Enterprise Linux",
	    enabled  => 1,
	    gpgcheck => 0,
	    before   => Yumrepo['ius'],
	}

	yumrepo { 'ius':
	    baseurl  => "http://dl.iuscommunity.org/pub/ius/stable/CentOS/6/x86_64",
	    descr    => "IUS Community Release Repo",
	    enabled  => 1, 
	    gpgcheck => 0,
	}

}

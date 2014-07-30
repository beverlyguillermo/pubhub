class git {

	package { "git":
		ensure => present,
	}

	# Adds git color by default
	# From: http://www.markhansen.co.nz/color-git/
	# Note: switched to --system to not rely on $HOME
	exec { 'git-color':
		command => "/usr/bin/git config --system --add color.ui true",
		require => Package['git'],
	}

	git::alias { 'aa':
		command => "add --all"
	}

	git::alias { 'st':
		command => "status"
	}

	git::alias { 'cm':
		command => "commit -m"
	}

	exec { 'default git user':
		command => "/usr/bin/git config --system user.name vagrant && /usr/bin/git config --system user.email vagrant@jhu.edu",
		require => Package['git'],
	}

}
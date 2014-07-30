define git::alias($aliasname = '', $command) {

	if $aliasname == '' {
		$a = $title
	} else {
		$a = $aliasname
	}

	# Unset the alias if it exists already
	exec { "git-unset-alias-${a}":
		command => "/usr/bin/git config --system --unset-all alias.${a}",
		onlyif => "/usr/bin/git config --get-all alias.${a}",
		require => Package['git'],
	}

	# Reset the alias to your new command
	exec { "git-alias-${a}":
		command => "/usr/bin/git config --system --add alias.${a} '${command}'",
		unless => "/usr/bin/git config --get-all alias.${a}",
		require => [Package['git'], Exec["git-unset-alias-${a}"]],
	}
}
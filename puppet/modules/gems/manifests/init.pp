class gems {
  
  exec { "gem install capistrano -v 2.12.0":
    unless => "gem list | grep capistrano",
  }
    
}

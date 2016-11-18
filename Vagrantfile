Vagrant.configure('2') do |config|
  config.ssh.forward_agent = true
  config.vm.box = 'cargomedia/debian-8-amd64-cm'

  config.vm.network :private_network, ip: '10.10.33.10'

  config.vm.synced_folder '.', '/home/vagrant/s3export_backup', :type => 'nfs'

  config.librarian_puppet.puppetfile_dir = 'puppet'
  config.librarian_puppet.placeholder_filename = '.gitkeep'
  config.librarian_puppet.resolve_options = {:force => true}
  config.vm.provision :puppet do |puppet|
    puppet.module_path = 'puppet/modules'
    puppet.manifests_path = 'puppet/manifests'
  end

  config.vm.provision 'shell', run: 'always', inline: [
    'cd /home/vagrant/s3export_backup',
    'composer --no-interaction install --dev',
    'bin/cm app setup',
  ].join(' && ')
end

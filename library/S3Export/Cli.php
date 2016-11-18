<?php

class S3Export_Cli extends CM_Cli_Runnable_Abstract {

    public function __construct(CM_InputStream_Interface $input = null, CM_OutputStream_Interface $output = null) {
        parent::__construct($input, $output);
    }

    public function listJobs() {
        $this->_getStreamOutput()->writeln(print_r($this->_getBackupManager()->listJobs(), true));
    }

    /**
     * @param string $jobId
     */
    public function getStatus($jobId) {
        $this->_getStreamOutput()->writeln(print_r($this->_getBackupManager()->getJobStatus($jobId), true));
    }

    /**
     * @param string      $devicePath
     * @param string|null $targetDirectory
     * @throws CM_Cli_Exception_Internal
     */
    public function verifyBackup($devicePath, $targetDirectory = null) {
        if (null === $targetDirectory) {
            $targetDirectory = $this->_getBackupManager()->getBucketName();
        }

        $device = new S3Export_Device($devicePath);
        if (!$device->hasPartitions()) {
            $device->fixPartitioning();
        }
        $device->mount();

        $filesystemBackupRootPath = $device->getMountpoint()->joinPath($targetDirectory)->getPathOnLocalFilesystem();
        $adapter = new CM_File_Filesystem_Adapter_Local($filesystemBackupRootPath);
        $filesystemBackup = new CM_File_Filesystem($adapter);

        $this->_getBackupManager()->verifyExport($this->_getStreamOutput(), $filesystemBackup);

        $device->unmount();
    }

    /**
     * @param           $manifestPath
     * @param string    $devicePath
     * @param bool|null $skipFormat
     * @param bool|null $dryRun
     */
    public function createJob($manifestPath, $devicePath, $skipFormat = null, $dryRun = null) {
        $manifestPath = (string) $manifestPath;
        if (!preg_match('/^\//', $manifestPath)) {
            $manifestPath = getcwd() . '/' . $manifestPath;
        }
        $devicePath = (string) $devicePath;
        $skipFormat = (bool) $skipFormat;
        $dryRun = (bool) $dryRun;
        $awsBackupManager = $this->_getBackupManager();

        $this->_getStreamOutput()->writeln('Preparing backup device');
        $device = new S3Export_Device($devicePath);
        if (!$skipFormat) {
            $device->format();
        }
        $device->mount();

        $this->_getStreamOutput()->writeln('Creating AWS backup job');
        $manifestFile = new CM_File($manifestPath);
        $job = $awsBackupManager->createJob($manifestFile->read(), $dryRun);
        $this->_getStreamOutput()->writeln("Job created, id: `{$job->getId()}`");
        $this->_getStreamOutput()->writeln('Storing AWS Signature on backup device');
        $awsBackupManager->storeJobSignatureOnDevice($job, $device);

        $device->unmount();
    }

    /**
     * @param string $jobId
     */
    public function cancelJob($jobId) {
        $this->_getBackupManager()->cancelJob($jobId);
        $this->_getStreamOutput()->writeln('Job successfully cancelled');
    }

    /**
     * @return S3Export_BackupManager
     * @throws CM_Exception_Invalid
     */
    protected function _getBackupManager() {
        return CM_Service_Manager::getInstance()->get('s3export-backup-manager');
    }

    public static function getPackageName() {
        return 's3export';
    }
}

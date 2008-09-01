<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/Task.php';
require_once 'phing/system/io/PhingFile.php';

/**
 * A phing task for adding one header to a set of files.
 * 
 * Parameters:
 *     - file: The location of the header file to load.
 *     - eol: Specifies what the end of line character are for use by 
 *       the fixlastline attribute.
 *     - failonerror: Stop process if one error occured.
 *     - preservelastmodified: Give the copied files the same 
 *       last modified time as the original source files.
 *     - verbose: Log the files that are being modified.
 * 
 * Here's a simple example of how this could be used:
 * <code>
 * <header file="${project.basedir}/LICENSE">
 *     <fileset dir="${project.basedir}/src">
 *         <include name="**\/*.txt"/>
 *     </fileset>
 * </header>
 * </code>
 * 
 * @author  Mehdi Kabab <pioupioum|at|gmail|dot|com>
 * @version 1.0
 * @package phing.tasks.ext
 * @todo    Manage encoding
 */
class HeaderTask extends Task
{
    /**
     * End of line, defaults to the PHP's Core constant PHP_EOL.
     *
     * @var    string
     * @access protected
     **/
    protected $_eol = PHP_EOL;

    /**
     * Preserve the last modified time for the modified files.
     *
     * @var boolean
     * @access protected
     **/
    protected $_preserveLastModified = false;

    /**
     * Level of verbosity.
     *
     * @var    int
     * @access protected
     **/
    protected $_verbosity = Project::MSG_VERBOSE;

    /**
     * What to do when it goes pear-shaped.
     * 
     * @var boolean
     * @access protected
     */
    protected $_failOnError = true;

    /**
     * All FileSet objects assigned to this task.
     *
     * @var    array
     * @access protected
     **/
    protected $_fileSets = array();

    /**
     * The file to load.
     * 
     * @var    PhingFile
     * @access private
     */
    private $_resource;

    /**
     * Sets the file to load.
     * 
     * @param  string|PhingFile $file The source file. Either a string or an PhingFile object
     * @return void
     * @access public
     **/
    public function setFile(PhingFile $file)
    {
        if (is_string($file))
            $file = new PhingFile($file);
        
        $this->_resource = $file;
    }

    /**
     * Set whether to fail when errors are encountered.
     * 
     * Stop the build process if true. If false, note errors to the output 
     * but keep going. Default is true.
     * 
     * @param  boolean $failOnError
     * @return void
     * @access public
     */
    public function setFailOnError($failOnError)
    {
        $this->_failOnError = (boolean) $failOnError;
    }

    /**
    * Give the modified files the same last modified time as the original files.
    * 
    * @param  boolean preserve If true preserve the modified time; default is false.
    * @return void
    * @access public
    */
    public function setPreserveLastModified($preserve)
    {
        $this->log('Preserve last modified time : true', $this->getVerbose());
        $this->_preserveLastModified = (boolean) $preserve;
    }

    /**
     * Get whether to give the modified files the same last modified time as
     * the original files.
     * 
     * @param  void
     * @return boolean The whether destination files will inherit the modification
     *                 times of the corresponding source files.
     * @access public
     */
    public function getPreserveLastModified()
    {
        return $this->_preserveLastModified;
    }

    /**
     * Specify the end of lines of each input files.
     * 
     * @param  string $oel The type of new line to add — cr, mac, lf, unix, crlf or dos
     * @return void
     * @access public
     */
    public function setEol($eol)
    {
        $eol = strtoupper((string) $eol);
        
        switch ($eol)
        {
            case 'CR':
            case 'MAC':
                $this->_eol = "\r";
                break;
            
            case 'lf':
            case 'unix':
                $this->_eol = "\n";
                break;
            
            case 'crlf':
            case 'dos':
                $this->_eol = "\r\n";
                break;
            
            default:
                $this->_eol = PHP_EOL;
        }
    }

    /**
     * Get the end of line.
     * 
     * @param  void
     * @return string
     * @access public
     */
     public function getEol()
    {
        return $this->_eol;
    }

    /**
     * Set verbose mode.
     * 
     * Used to force listing of all names of modified files.
     * 
     * @param  boolean $verbose Whether to output the names of modified files.
     *                          Default is false.
     * @return void
     * @access public
     */
    public function setVerbose($verbose)
    {
        $this->_verbosity = ((boolean) $verbose) ? Project::MSG_INFO : Project::MSG_VERBOSE;
    }

    /**
     * Get the verbose mode.
     * 
     * @param  void
     * @return boolean
     * @access public
     */
     public function getVerbose()
    {
        return $this->_verbosity;
    }

    /**
     * Nested creator, creates a FileSet for this task.
     *
     * @param  void
     * @return FileSet The created FileSet object
     * @access public
     */
    public function createFileSet()
    {
        $count = array_push($this->_fileSets, new FileSet());
        
        return $this->_fileSets[--$count];
    }

    /**
     * The main entry point where everything gets in motion.
     *
     * @param  void
     * @return boolean True on success
     * @throws BuildException
     * @access public
     */
    public function main()
    {
        $this->_validateAttributes();
        
        return $this->_proccess();
    }

    /**
     * Perform this task.
     * 
     * @param  void
     * @return boolean True on success
     * @throws BuildException
     * @access protected
     **/
    protected function _proccess()
    {
        $this->log(
            'Loading header: ' . $this->_resource . ' (' . $this->_resource->length() . ' Bytes)',
            $this->getVerbose()
        );
        
        $header  = $this->_loadResource();
        $project = $this->getProject();
        
        foreach ($this->_fileSets as $fileset)
        {
            $directoryset = $fileset->getDirectoryScanner($project);
            $fromDir      = $fileset->getDir($project);
            $srcFiles     = $directoryset->getIncludedFiles();
            
            foreach ($srcFiles as $srcFile)
            {
                $file = new PhingFile($fromDir . DIRECTORY_SEPARATOR . $srcFile);
                
                try
                {
                    $this->log('Reading ' . $file, $this->getVerbose());
                    $buffer = $this->_readFile($file);
                
                    $buffer = $this->_concat($header, $buffer);
                
                    $this->log('Writing ' . $file, $this->getVerbose());
                    $this->_writeFile($file, $buffer);
                }
                catch (IOException $e)
                {
                    $msg = 'Cannot update file!';
                    
                    if ($this->_failOnError)
                    {
                        throw new BuildException('Cannot update file! ' . $e->getMessage() , $e);
                    }
                    else
                    {
                        $this->log($msg, $this->getLocation());
                    }
                }
                
                unset($file);
            }
            
        }
        
        return true;
    }

    /**
     * Load the header file.
     * 
     * @param  void
     * @return string The resource's content
     * @access protected
     **/
    protected function _loadResource()
    {
        return file_get_contents($this->_resource->getPath());
    }

    /**
     * Reading a file.
     * 
     * @param  PhingFile $file The file to read
     * @return string
     * @access protected
     **/
    protected function _readFile(PhingFile $file)
    {
        $input  = new FileReader($file);
        $buffer = $input->read();
        $input->close();
        
        return $buffer;
    }

    /**
     * Writing a file.
     * 
     * @param  PhingFile $file    The file to write
     * @param  mixed     $content The file's content
     * @return void
     * @throws BuildException
     * @access protected
     **/
    protected function _writeFile(PhingFile $file, $content)
    {
        if ($this->_preserveLastModified)
        {
            $lastModified = $file->lastModified();
        }
        
        $output = new FileWriter($file);
        $output->write($content);
        $output->close();
        
        if ($this->_preserveLastModified)
        {
            $file->setLastModified($lastModified);
        }
    }

    /**
     * Concat header and content.
     * 
     * @param  string $header
     * @param  string $content
     * @return string
     * @access protected
     **/
    protected function _concat($header, $content)
    {
        if (is_array($content))
        {
            switch (count($content))
            {
                case 0:
                    $this->log('No content to write!', $this->getVerbose());
                    break;
                
                case 1:
                    $content = $content[0];
                    break;
                
                default:
                    $content = implode($this->getEol(), $content);
                    break;
            }
        }
        
        return $header . $this->getEol() . $content;
    }

    /**
     * Validates attributes coming in from XML.
     *
     * @param  void
     * @return void
     * @throws BuildException
     * @access protected
     */
    protected function _validateAttributes()
    {
        if (null === $this->_resource)
        {
            throw new BuildException('You must specify a file to load.', $this->getLocation());
        }
        
        if (0 === count($this->_fileSets))
        {
            throw new BuildException('You must specify a fileset.', $this->getLocation());
        }
        
        if ($this->_resource->exists())
        {
            if ($this->_resource->isDirectory())
            {
                throw new BuildException('Cannot load a directory as a file.', $this->getLocation());
            }
            
            try
            {
                if (0 ===  $this->_resource->length())
                {
                    $this->log('The file ' . $this->_resource . ' is empty !', $this->getVerbose());
                }
            }
            catch (IOException $e)
            {
                throw new BuildException($e->getMessage(), $this->getLocation());
            }
        }
        else
        {
            throw new BuildException($this->_resource . ' doesn\'t exist', $this->getLocation());
        }
    }

}

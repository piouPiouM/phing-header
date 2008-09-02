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

/**
 * @see HeaderTask
 */
require_once 'phing/tasks/ext/HeaderTask.php';

/**
 * A phing task for adding a heading comment block to a set of php files.
 * 
 * Warning: line contains the first php tag (i.e. <code><?php</code>) is deleted.
 * 
 * Parameters:
 *     - file: The location of the file to load.
 *     - eol: Specifies what the end of line character.
 *     - preservelastmodified: Give the copied files the same 
 *       last modified time as the original source files.
 *     - verbose: Log the files that are being modified.
 * 
 * Here's a simple example of how this could be used:
 * <code>
 * <phpheader file="${project.basedir}/LICENSE">
 *     <fileset dir="${project.basedir}/src">
 *         <include name="**\/*.php"/>
 *     </fileset>
 * </phpheader>
 * </code>
 * 
 * @author  Mehdi Kabab <pioupioum|at|gmail|dot|com>
 * @version 1.0
 * @package phing.tasks.ext
 * @todo    Manage encoding
 */
class HeaderPhpTask extends HeaderTask
{

    /**
    * Reading a file.
    * 
    * Read a file and strip the fisrt line contains php tag.
    * 
    * @param  PhingFile $file The file to read
    * @return string
    * @access protected
    **/
    protected function _readFile(PhingFile $file)
    {
        $content = file($file->getPath(), FILE_IGNORE_NEW_LINES);
        
        while (list($k, $v) = each($content))
        {
            $pos = strpos($v, '<?php');
            
            if (false !== $pos)
            {
                $this->log(
                    sprintf('PHP tag found at line %s column %s', $k + 1, ++$pos),
                    $this->getVerbose()
                );
                
                unset($content[$k]);
                break;
            }
        }
        unset($k, $v, $pos);
        
        return implode($this->getEol(), $content);
    }

   /**
    * Concat php tag, header and content.
    * 
    * @param  string $header
    * @param  string $content
    * @return string
    * @access protected
    **/
    protected function _concat($header, $content)
    {
        return '<?php' . $this->getEol() . $header . $this->getEol() . $content;
    }

}

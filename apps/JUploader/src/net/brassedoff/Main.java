/*
 * Main.java
 *
 * Created on 18 August 2006, 20:35
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package net.brassedoff;

import java.awt.Toolkit;
import java.io.BufferedReader;
import java.io.FileReader;
import javax.swing.JOptionPane;
import org.apache.commons.httpclient.*;
import org.apache.commons.httpclient.methods.*;

/**
 *
 * @author david
 */
public class Main {
    
    static String geoURL = new String("http://geograph/juploader.php");
    
    static String [] imageClassList;
    static boolean noCache = true;
    static int geoUserid = 0;

    
    /** Creates a new instance of Main */
    public Main() {
    }
    
    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {

        // check that we've got the correct number of command line arguments
        
        if (args.length != 1) {
            System.out.println("Invalid command line arguments");
            System.exit(1);
        }
        
        geoURL = "http://" + args[0] + "/juploader.php";
        
        // Initialise application with data from server, esp. class list        
                
        LoadCache();
        
        // TODO Get user authentication information (login) and validate on server
        
        UploadManager ul = new UploadManager();

        ul.setVisible(true);        
        
    }
    
    private static void LoadCache() {
        
        // load all the static stuff from the cache
        // warn if there's no cache file and make sure the rest of the
        // app knows there's stuff missing
        
        BufferedReader inp;
        try {
            inp = new BufferedReader(new FileReader("juppycache.xml"));
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(null, "No cache present\nYou will need to log in to\n" +
                    "Geograph before you can use JUppy");
            Toolkit.getDefaultToolkit().beep();
            return;
        }
        
        StringBuffer cacheData = new StringBuffer(5000);
        
        try {
            String cacheLine;
            do {
                cacheLine = inp.readLine();
                if (cacheLine != null) {
                    cacheData.append(cacheLine);
                }
            } while (cacheLine != null);
            
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(null, "No cache present\nYou will need to log in to\n" +
                    "Geograph before you can use JUppy");
            Toolkit.getDefaultToolkit().beep();
            return;            
        }
        
                
        imageClassList = XMLHandler.getXMLField(cacheData.toString(), "classlist").split("}");
        Main.noCache = false;
    }
    
}

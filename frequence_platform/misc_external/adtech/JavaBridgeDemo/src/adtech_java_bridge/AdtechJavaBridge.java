package adtech_java_bridge;

import java.security.NoSuchProviderException;


public class AdtechJavaBridge {

    public static AdTech adtech = new AdTech();
    public static final String JAVABRIDGE_PORT = "8080";
    static final php.java.bridge.JavaBridgeRunner runner = php.java.bridge.JavaBridgeRunner.getInstance(JAVABRIDGE_PORT);
    
    public static void main(String[] args) throws InterruptedException, NoSuchProviderException, Exception
    {
	adtech.connect();
 	//adtech.create_new_campaign_flight_from_template("4977243", "TEST");
	runner.waitFor();
	System.exit(0);
    }
}

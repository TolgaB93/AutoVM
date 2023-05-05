from pysphere import VIProperty, MORTypes        # Import necessary modules
from server import get_server, response         # Import custom functions
data = {}                                        # Create an empty dictionary to store data
data['storages'] = {}                            # Create an empty dictionary to store storage data
data['memory'] = {}                              # Create an empty dictionary to store memory data
data['cpu'] = {}                                 # Create an empty dictionary to store CPU data

serve = get_server()                             # Get server object using custom function get_server()

if not serve:                                    # If server object not found, return response(False)
  response(False)
  
if serve:                                        # If server object found, execute the following code

  try:
    result = serve.get_datastores().items()      # Get datastores and their properties using serve.get_datastores()
  except:
    pass
  
  if result:                                     # If datastores found, execute the following code for each datastore
    for firstv, secondv in result:
      try:
        props = VIProperty(serve, firstv)        # Get properties for datastore
      except:
        continue
		  
      gigabyte = props.summary.capacity          # Get total capacity of datastore
      free = props.summary.freeSpace              # Get free space in datastore
      
      if 'datastore' in secondv:                  # If the datastore name contains 'datastore'
        data['storages'][secondv] = {}            # Create a nested dictionary in data['storages'] for the datastore
        data['storages'][secondv]['hash'] = firstv # Add datastore hash to nested dictionary
        data['storages'][secondv]['capacity'] = gigabyte # Add datastore capacity to nested dictionary
        data['storages'][secondv]['free'] = free  # Add free space to nested dictionary
		
  try:
    hosts = serve.get_hosts().items()            # Get hosts and their properties using serve.get_hosts()
  except:
    pass
	  	
  if hosts:                                      # If hosts found, execute the following code for each host
    for h_mor, h_name in hosts:
      try:
        propsV = VIProperty(serve, h_mor)         # Get properties for host
      except:
        continue
			
      memory = propsV.hardware.memorySize         # Get total memory of host
      usage_memory = propsV.summary.quickStats.overallMemoryUsage # Get memory usage of host
      cpu_mhz = propsV.summary.hardware.cpuMhz    # Get CPU speed of host
      core = propsV.summary.hardware.numCpuCores  # Get number of CPU cores of host
      usage_cpu = propsV.summary.quickStats.overallCpuUsage # Get CPU usage of host
			
      if memory:
        data['memory']['size'] = memory           # Add total memory to data['memory']
      if usage_memory:
        data['memory']['usage'] = usage_memory    # Add memory usage to data['memory']
      if cpu_mhz:
        data['cpu']['size'] = cpu_mhz             # Add CPU speed to data['cpu']
      if core:
        data['cpu']['core'] = core                # Add number of CPU cores to data['cpu']
      if usage_cpu:
        data['cpu']['usage'] = usage_cpu          # Add CPU usage to data['cpu']
		  
response(True, data)                             # Return response(True) with data dictionary

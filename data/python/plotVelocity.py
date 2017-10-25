import numpy as np
import matplotlib.pyplot as plt 
from rdp import rdp 
import pandas as pd





def plotVelocity():
	velocityData = pd.read_csv('../output/velocity.csv', header=None, names=['distance', 'velocity'])
	# velocitySMAData = pd.read_csv('../output/velocitySMA.csv', header=None, names=['distance', 'velocity'])
	# velocityRDPData = pd.read_csv('../output/velocityRDP.csv', header=None, names=['distance', 'velocity'])
	velocitySMARDPData = pd.read_csv('../output/velocitySMARDP.csv', header=None, names=['distance', 'velocity'])
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
			

	plt.subplot(1, 1, 1)
	# r = rdp(velocityData.as_matrix(), epsilon=0.5)
	
	# vel = velocityData['velocity'].rolling(5, center=True).mean()
	plt.plot(velocityData['distance'], velocityData['velocity'], 'r', label="velocity")
	# plt.plot(velocitySMAData['distance'], velocitySMAData['velocity'], 'b', label="sma")
	# plt.plot(velocityRDPData['distance'], velocityRDPData['velocity'], 'g', label="rdp")
	plt.plot(velocitySMARDPData['distance'], velocitySMARDPData['velocity'], 'k', label="sma rdp")
	plt.plot(np.array([0, velocitySMARDPData['distance'][-1:]]), np.array([1000/198,1000/198]), 'g', label="mean")
	plt.plot(np.array([0, velocitySMARDPData['distance'][-1:]]), np.array([1000/295*1.1,1000/295*1.1]), 'y', label="training pace")
	plt.plot(np.array([0, velocitySMARDPData['distance'][-1:]]), np.array([1000/268,1000/268]), 'b', label="activity pace")
	plt.legend(fontsize="small", loc="lower center")


	plt.show()

plotVelocity()
# plotDifferenceElev()

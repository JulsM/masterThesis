import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D
from sklearn import preprocessing


colors = ['red', 'blue', 'green', 'yellow', 'cyan', 'grey', 'purple', 'coral', 'k', 'saddlebrown']

def plotSegmentEval():
	data = pd.read_csv('../python/athleteSegmentCrossVal.csv', skipinitialspace=True, skiprows=1, names=['MAE', 'RMSE', 'Acc', 'Sim', 'Names'])
	fig = plt.figure(figsize=(12, 8), dpi=100, facecolor='w')
	plt.subplot(311)

	data['MAE'].plot.kde()
	data['RMSE'].plot.kde()
	
	plt.xlabel('Minutes')
	plt.grid()
	plt.xticks(np.arange(-5, 50, 5))
	plt.xlim(-5, 50)
	plt.legend(fontsize="small", loc="upper right")

	plt.subplot(312)
	data['MAE'].hist(alpha=0.5, bins = 70, label='MAE')
	data['RMSE'].hist(alpha=0.5, bins = 70, label='RMSE')
	plt.xticks(np.arange(-5, 50, 5))
	plt.xlim(-5, 50)
	plt.legend(fontsize="small", loc="upper right")
	plt.xlabel('Minutes')
	plt.ylabel('Histogram')

	plt.subplot(313)
	acc = data['Acc']
	acc.plot.kde()

	data['Sim'].plot.kde(label='Sim')
	plt.xlabel('Percent')
	plt.legend(fontsize="small", loc="upper right")
	plt.xticks(np.arange(60, 105, 5))
	plt.xlim(60, 105)
	plt.grid()

	### bars

	fig = plt.figure(figsize=(12, 12), dpi=100, facecolor='w')
	plt.subplot(411)
	data['MAE'].plot.bar()
	plt.plot(np.array([-1, 30]), np.array([4, 4]), color='r', lw=1)
	plt.xticks(data.index, data.Names, fontsize=8)

	plt.ylabel('Minutes MAE')
	plt.yticks(np.arange(0, 40, 10))
	plt.grid()

	plt.subplot(412)
	data['RMSE'].plot.bar(color='green')
	plt.plot(np.array([-1, 30]), np.array([4, 4]), color='r', lw=1)
	plt.xticks(data.index, data.Names, fontsize=8)

	plt.ylabel('Minutes RMSE')
	plt.yticks(np.arange(0, 45, 10))
	plt.grid()

	plt.subplot(413)
	acc = data['Acc']
	acc.plot.bar(color='purple')
	plt.plot(np.array([-1, 30]), np.array([95, 95]), color='r', lw=1)
	plt.xticks(data.index, data.Names, fontsize=8)

	plt.ylabel('%% Accuracy')
	plt.yticks(np.arange(0, 100, 20))
	# plt.xlim(60, 105)
	plt.grid()

	plt.subplot(414)
	data['Sim'].plot.bar(color='purple')
	plt.plot(np.array([-1, 30]), np.array([95, 95]), color='r', lw=1)
	plt.xticks(data.index, data.Names, fontsize=8)

	plt.ylabel('%% Similarity')
	plt.yticks(np.arange(0, 100, 20))
	# plt.xlim(60, 105)
	plt.grid()


	plt.tight_layout()
	plt.show()


def plotActivityEval():

	data = pd.read_csv('../python/athleteCrossVal.csv', skipinitialspace=True, skiprows=1, names=['MAE', 'RMSE', 'Acc', 'Names'])
	fig = plt.figure(figsize=(12, 8), dpi=100, facecolor='w')
	plt.subplot(311)

	data['MAE'].plot.kde()
	data['RMSE'].plot.kde()
	
	plt.xlabel('Minutes')
	plt.grid()
	plt.xticks(np.arange(-5, 50, 5))
	plt.xlim(-5, 50)
	plt.legend(fontsize="small", loc="upper right")

	plt.subplot(312)
	data['MAE'].hist(alpha=0.5, bins = 70, label='MAE')
	data['RMSE'].hist(alpha=0.5, bins = 70, label='RMSE')
	plt.xticks(np.arange(-5, 50, 5))
	plt.xlim(-5, 50)
	plt.legend(fontsize="small", loc="upper right")
	plt.xlabel('Minutes')
	plt.ylabel('Histogram')

	plt.subplot(313)
	acc = data['Acc']*100
	acc.plot.kde()

	plt.xlabel('Percent')
	plt.legend(fontsize="small", loc="upper right")
	plt.xticks(np.arange(60, 105, 5))
	plt.xlim(60, 105)
	plt.grid()
	plt.tight_layout()

	### bars

	fig = plt.figure(figsize=(12, 12), dpi=100, facecolor='w')
	plt.subplot(311)
	data['MAE'].plot.bar()
	plt.plot(np.array([-1, 60]), np.array([4, 4]), color='r', lw=1)
	plt.xticks(data.index, data.Names, fontsize=8)

	plt.ylabel('Minutes MAE')
	plt.yticks(np.arange(0, 40, 10))
	plt.grid()

	plt.subplot(312)
	data['RMSE'].plot.bar(color='green')
	plt.plot(np.array([-1, 60]), np.array([4, 4]), color='r', lw=1)
	plt.xticks(data.index, data.Names, fontsize=8)

	plt.ylabel('Minutes RMSE')
	plt.yticks(np.arange(0, 45, 10))
	plt.grid()

	plt.subplot(313)
	acc = data['Acc']*100
	acc.plot.bar(color='purple')
	plt.plot(np.array([-1, 60]), np.array([95, 95]), color='r', lw=1)
	plt.xticks(data.index, data.Names, fontsize=8)

	plt.ylabel('%% Accuracy')
	plt.yticks(np.arange(0, 100, 20))
	# plt.xlim(60, 105)
	plt.grid()


	plt.tight_layout()
	plt.show()
	




# plotSegmentEval()
plotActivityEval()

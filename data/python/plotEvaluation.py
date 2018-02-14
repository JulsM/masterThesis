import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D
from sklearn import preprocessing

# label = ['Athlete 1', 'Athlete 2','Athlete 3','Athlete 4','Athlete 5','Athlete 6','Athlete 7','Athlete 8','Athlete 9','Athlete 10','Athlete 11','Athlete 12',]
colors = ['red', 'blue', 'green', 'yellow', 'cyan', 'grey', 'purple', 'coral', 'k', 'saddlebrown']

def plotSegmentEval():
	data = pd.read_csv('../python/athleteSegmentCrossVal.csv', skipinitialspace=True, skiprows=1, names=['MAE', 'RMSE', 'Acc', 'Sim', 'Names'])
	fig = plt.figure(figsize=(12, 10), dpi=100, facecolor='w')
	data = data[(data.Names != 'Andre Romao') & (data.Names != 'Martin B')].reset_index(drop=True)

	print('Mean accuracy: {:.2f} %, mean similarity: {:.2f} %, RMSE: {:.2f}, MAE: {:.2f}'.format(np.mean(data.Acc), np.mean(data.Sim), np.mean(data.RMSE),np.mean(data.MAE)))
	
	plt.subplot(311)

	data['MAE'].plot.kde()
	data['RMSE'].plot.kde()
	
	plt.xlabel('Minutes')
	plt.grid(linestyle='dotted', linewidth=1)
	plt.xticks(np.arange(-5, 40, 5))
	plt.xlim(-5, 40)
	plt.legend(fontsize="small", loc="upper right")

	plt.subplot(312)
	data['MAE'].hist(alpha=0.5, bins = 70, label='MAE')
	data['RMSE'].hist(alpha=0.5, bins = 70, label='RMSE')
	plt.xticks(np.arange(-5, 40, 5))
	plt.xlim(-5, 40)
	plt.legend(fontsize="small", loc="upper right")
	plt.xlabel('Minutes')
	plt.ylabel('Histogram')
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(313)
	acc = data['Acc']
	acc.plot.kde()

	data['Sim'].plot.kde(label='Sim')
	plt.xlabel('Percent')
	plt.legend(fontsize="small", loc="upper right")
	plt.xticks(np.arange(60, 105, 5))
	plt.xlim(60, 105)
	plt.grid(linestyle='dotted', linewidth=1)

	# plt.savefig('segment_histo.png')

	### bars

	fig = plt.figure(figsize=(12, 16), dpi=100, facecolor='w')
	plt.subplot(411)
	data['MAE'].plot.bar()
	plt.plot(np.array([-1, 60]), np.array([np.mean(data['MAE']), np.mean(data['MAE'])]), color='r', lw=1)
	plt.xticks(data.index, data.Names, fontsize=8)

	plt.ylabel('Minutes MAE')
	plt.yticks(np.arange(0, 40, 10))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(412)
	data['RMSE'].plot.bar(color='green')
	plt.plot(np.array([-1, 60]), np.array([np.mean(data['RMSE']), np.mean(data['RMSE'])]), color='r', lw=1)
	plt.xticks(data.index, (data.index + 1), fontsize=8)

	plt.ylabel('RMSE (min)')
	plt.yticks(np.arange(0, 45, 10))
	plt.xlabel('Athletes')
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(413)
	acc = data['Acc']
	acc.plot.bar(color='purple')
	plt.plot(np.array([-1, 60]), np.array([np.mean(data['Acc']), np.mean(data['Acc'])]), color='r', lw=1)
	plt.xticks(data.index, data.index+1, fontsize=8)

	# plt.ylim(70, 100)
	plt.ylim(20, 100)
	plt.ylabel('Accuracy (%)')
	plt.yticks(np.arange(70, 105, 5))
	plt.xlabel('Athletes')
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(414)
	data['Sim'].plot.bar(color='purple')
	plt.plot(np.array([-1, 60]), np.array([np.mean(data['Sim']), np.mean(data['Sim'])]), color='r', lw=1)
	plt.xticks(data.index, data.index + 1, fontsize=8)

	plt.ylim(20, 100)
	plt.ylabel('Similarity (%)')
	plt.yticks(np.arange(20, 105, 10))
	plt.xlabel('Athletes')
	plt.grid(linestyle='dotted', linewidth=1)


	plt.tight_layout()
	# plt.savefig('segment_acc_sim.png')
	plt.show()


def plotActivityEval():

	data = pd.read_csv('../python/athleteCrossVal.csv', skipinitialspace=True, skiprows=1, names=['MAE', 'RMSE', 'Acc', 'Names'])
	fig = plt.figure(figsize=(12, 8), dpi=100, facecolor='w')
	plt.subplot(311)

	data['MAE'].plot.kde()
	data['RMSE'].plot.kde()
	
	plt.xlabel('Minutes')
	plt.grid(linestyle='dotted', linewidth=1)
	plt.xticks(np.arange(-5, 25, 5))
	plt.xlim(-5, 25)
	plt.legend(fontsize="small", loc="upper right")

	plt.subplot(312)
	data['MAE'].hist(alpha=0.5, bins = 40, label='MAE')
	data['RMSE'].hist(alpha=0.5, bins = 40, label='RMSE')
	plt.xticks(np.arange(-5, 25, 5))
	plt.xlim(-5, 25)
	plt.legend(fontsize="small", loc="upper right")
	plt.xlabel('Minutes')
	plt.ylabel('Histogram')

	plt.subplot(313)
	acc = data['Acc']*100
	acc.plot.kde()

	plt.xlabel('Percent')
	plt.legend(fontsize="small", loc="upper right")
	plt.xticks(np.arange(70, 105, 5))
	plt.xlim(70, 105)
	plt.grid(linestyle='dotted', linewidth=1)
	plt.tight_layout()

	plt.savefig('activity_hist.png')

	### bars

	fig = plt.figure(figsize=(12, 12), dpi=100, facecolor='w')
	plt.subplot(311)
	data['MAE'].plot.bar()
	plt.plot(np.array([-1, 60]), np.array([np.mean(data['MAE']), np.mean(data['MAE'])]), color='r', lw=1)
	plt.xticks(data.index, data.index+1, fontsize=8)

	plt.ylabel('Minutes MAE')
	plt.yticks(np.arange(0, 20, 5))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(312)
	data['RMSE'].plot.bar(color='green')
	plt.plot(np.array([-1, 60]), np.array([np.mean(data['RMSE']), np.mean(data['RMSE'])]), color='r', lw=1)
	plt.xticks(data.index, data.index+1, fontsize=8)

	plt.ylabel('RMSE (min)')
	plt.yticks(np.arange(0, 25, 5))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(313)
	acc = data['Acc']*100
	acc.plot.bar(color='purple')
	plt.plot(np.array([-1, 60]), np.array([np.mean(acc), np.mean(acc)]), color='r', lw=1)
	plt.xticks(data.index, data.index+1, fontsize=8)

	plt.ylabel('Accuracy (%)')
	plt.yticks(np.arange(70, 105, 5))
	plt.ylim(70, 100)
	plt.grid(linestyle='dotted', linewidth=1)


	plt.tight_layout()
	plt.savefig('activity_acc.png')
	plt.show()
	




# plotSegmentEval()
plotActivityEval()

import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D
from sklearn import preprocessing

label = ['Athlete 1', 'Athlete 2','Athlete 3','Athlete 4','Athlete 5','Athlete 6','Athlete 7','Athlete 8','Athlete 9','Athlete 10','Athlete 11','Athlete 12',]
	
colors = ['dodgerblue', 'greenyellow', 'coral', 'darkorchid', 'darkorange', 'mediumturquoise', 'moccasin',  
'limegreen', 'blueviolet',  'palevioletred', 'palegreen', 'indianred']

def plotData():	
	data = pd.read_csv('../output/Julian Maurer/athletesStudy.csv', skipinitialspace=True)
	
	fig = plt.figure(figsize=(14, 8), dpi=100, facecolor='w')
	# plt.suptitle('Participants: '+str(len(data.index)), fontsize= 20)
	plt.subplot(231)
	gender = data.loc[:,'gender'].value_counts()
	plt.axis('equal')
	plt.pie(gender, labels=gender.index.tolist(), autopct='%1.1f%%', colors=colors, 
		radius=1.2, wedgeprops={'linewidth':0.3},labeldistance=1.2, textprops={'fontsize':'small'})

	plt.subplot(232)
	data['weeklyMileage'] /= 1000
	# print(np.mean(data['weeklyMileage']),np.std(data['weeklyMileage']))
	data['weeklyMileage'].plot.bar()
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('weekly mileage km')

	plt.subplot(233)
	# print(np.mean(data['avgElevGain']),np.std(data['avgElevGain']))
	data['avgElevGain'].plot.bar()
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('elevation gain m')

	plt.subplot(234)
	data['avgTrainPace'].plot.bar()
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('training pace m/s')

	plt.subplot(235)
	# print(np.mean(data['numRaces']),np.std(data['numRaces']))
	data['numRaces'].plot.bar()
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('number of races')
	# plt.grid(linestyle='dotted', linewidth=1)

	

	plt.tight_layout()
	plt.show()

def writeRaces():
	data = pd.read_csv('../output/Julian Maurer/racesStudy.csv', skipinitialspace=True)
	athlete = data[data.name == 'Julian Maurer']
	s = athlete.sort_values(by=['distance'])
	print('Julian Maurer')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Lauflinchen RM']
	s = athlete.sort_values(by=['distance'])
	print('Lauflinchen RM')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Kai K.']
	s = athlete.sort_values(by=['distance'])
	print('Kai K.')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Martin  Mühlhan ']
	s = athlete.sort_values(by=['distance'])
	print('Martin  Mühlhan ')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Monika Paul']
	s = athlete.sort_values(by=['distance'])
	print('Monika Paul')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Chris WA']
	s = athlete.sort_values(by=['distance'])
	print('Chris WA')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Kai Detemple']
	s = athlete.sort_values(by=['distance'])
	print('Kai Detemple')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Alexander Zeiner']
	s = athlete.sort_values(by=['distance'])
	print('Alexander Zeiner')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Martin B.']
	s = athlete.sort_values(by=['distance'])
	print('Martin B.')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Peter Petto']
	s = athlete.sort_values(by=['distance'])
	print('Peter Petto')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Conny Ziegler']
	s = athlete.sort_values(by=['distance'])
	print('Conny Ziegler')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))

	athlete = data[data.name == 'Florian Daiber']
	s = athlete.sort_values(by=['distance'])
	print('Florian Daiber')
	print(s[['distance', 'elevGain', 'elapsedTime', 'hilly', 'date']].reset_index(drop=True))


def plotResults():
	data = pd.read_excel('studyresults.xlsx', sheetname=0)
	fig = plt.figure(figsize=(8, 8), dpi=100, facecolor='w')
	

	plt.axis('equal')
	plt.scatter(data.realTime, data.predTime, s=8, color='deepskyblue', label='activity based method')
	plt.scatter(data.realTime, data.segmentPredTime, s=8, color='darkorange', label='segment based method')
	plt.scatter(data.realTime, data.Riegel, s=8, color='purple', label='Riegel')
	plt.xlabel('Actual time')
	plt.ylabel('Predicted time')
	plt.xlim(30, 65)
	plt.ylim(30, 65)
	plt.plot([30, 65], [30, 65], color='grey', alpha=0.5, lw=0.5)

	plt.legend()
	plt.grid()
	# plt.tight_layout()
	plt.show()

	

	


def plotAccResults():
	data = pd.read_excel('studyresults.xlsx', sheetname=0)
	print(np.mean(data['accuracy']),np.std(data['accuracy']))
	plt.figure(figsize=(8, 8), dpi=100, facecolor='w')
	plt.subplot(211)
	plt.title('Mean accuracy '+str(round(np.mean(data['accuracy']), 2)), fontsize=10)
	data['accuracy'].plot.bar(color='deepskyblue')
	plt.plot(np.array([-1, 30]), np.array([np.mean(data['accuracy']), np.mean(data['accuracy'])]), color='r', lw=0.5)
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Accuracy %')
	plt.yticks(np.arange(0, 100, 20))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(212)
	print(np.mean(data['error']),np.std(data['error']))
	plt.title('Mean error '+str(round(np.mean(data['error']), 2)), fontsize=10)
	data['error'].plot.bar(color='deepskyblue')
	plt.plot(np.array([-1, 30]), np.array([np.mean(data['error']), np.mean(data['error'])]), color='r', lw=0.5)
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Error min')
	plt.yticks(np.arange(0, 10, 2))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.tight_layout()
	plt.show()

def plotRiegel():
	data = pd.read_excel('studyresults.xlsx', sheetname=0)
	plt.figure(figsize=(8, 8), dpi=100, facecolor='w')

	plt.subplot(211)
	plt.title('Mean accuracy '+str(round(np.mean(data['RiegelAccuracy']), 2)), fontsize=10)
	data['RiegelAccuracy'].plot.bar(color='purple')
	plt.plot(np.array([-1, 30]), np.array([np.mean(data['RiegelAccuracy']), np.mean(data['RiegelAccuracy'])]), color='r', lw=0.5)
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Accuracy %')
	plt.yticks(np.arange(0, 100, 20))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(212)
	error = abs(data['Riegel'] - data['realTime'])
	plt.title('Mean error '+str(round(np.mean(error), 2)), fontsize=10)
	error.plot.bar(color='purple')
	plt.plot(np.array([-1, 30]), np.array([np.mean(error), np.mean(error)]), color='r', lw=0.5)
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Error min')
	plt.yticks(np.arange(0, 6, 1))
	plt.grid(linestyle='dotted', linewidth=1)


	plt.tight_layout()
	plt.show()


def plotSegmentResults():
	data = pd.read_excel('studyresults.xlsx', sheetname=0)
	plt.figure(figsize=(7, 8), dpi=100, facecolor='w')
	plt.subplot(311)
	plt.title('Mean accuracy '+str(round(np.mean(data['segmentAccuracy']), 2)), fontsize=10)
	data['segmentAccuracy'].plot.bar(color='darkorange')
	plt.plot(np.array([-1, 30]), np.array([np.mean(data['segmentAccuracy']), np.mean(data['segmentAccuracy'])]), color='r', lw=0.5)
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Accuracy %')
	plt.yticks(np.arange(0, 100, 20))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(312)
	plt.title('Mean similarity '+str(round(np.mean(data['segmentSimilarity']), 2)), fontsize=10)
	data['segmentSimilarity'].plot.bar(color='darkorange')
	plt.plot(np.array([-1, 30]), np.array([np.mean(data['segmentSimilarity']), np.mean(data['segmentSimilarity'])]), color='r', lw=0.5)
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Similarity %')
	plt.yticks(np.arange(0, 100, 20))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(313)
	plt.title('Mean RMSE '+str(round(np.mean(data['segmentRMSE']), 2)), fontsize=10)
	data['segmentRMSE'].plot.bar(color='darkorange')
	plt.plot(np.array([-1, 30]), np.array([np.mean(data['segmentRMSE']), np.mean(data['segmentRMSE'])]), color='r', lw=0.5)
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('RMSE sec')
	plt.yticks(np.arange(0, 80, 20))
	plt.grid(linestyle='dotted', linewidth=1)

	plt.tight_layout()
	plt.show()

	



# writeRaces()
# plotData()
plotResults()
# plotAccResults()
# plotRiegel()
# plotSegmentResults()

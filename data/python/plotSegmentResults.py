import numpy as np
import matplotlib.pyplot as plt 
from rdp import rdp 
import pandas as pd





def plotSegments(athlete):
	data = pd.read_csv('../output/'+athlete+'/studySegmentResults.csv', skipinitialspace=True)
	segments = pd.read_csv('../output/'+athlete+'/segments.csv', skipinitialspace=True, names=('start', 'alt', 'length'))
	fig = plt.figure(figsize=(14, 4), dpi=100, facecolor='w')
			

	x = segments.iloc[:-1, 0] + segments.iloc[:-1, 2] / 2
	y = ((data.iloc[:, 0] - data.iloc[:, 1]) / data.iloc[:, 1]) * 100
	
	start = np.mean(segments.iloc[:, 1])
	plt.plot(segments.iloc[:, 0], segments.iloc[:, 1], 'b', lw=0.5, label="Elevation profile")
	rects = plt.bar(x, y, 100, start, color='g', label="Error of predicted time in %")
	plt.plot([0,segments.iloc[-1:, 0]], [start,start], 'g', lw=0.2)
	plt.xlabel('Distance in meter')
	plt.ylabel('Altitude in meter')
	plt.legend()

	i = 0
	for rect in rects:
		height = rect.get_height()
		plt.text(rect.get_x() + rect.get_width()/2., start + height + np.sign(height)*3,'%.1f' % float(y[i]), ha='center', va='center', fontsize=6)
		i+=1

	plt.show()

plotSegments('Florian Daiber')

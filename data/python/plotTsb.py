import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
import matplotlib.dates as mdates




def plotTsb():
	data = pd.read_csv('../output/tsbModel.csv', skiprows=1, names=['date', 'tss', 'atl', 'ctl', 'vo2max', 'acType'], parse_dates=['date'])
	
	month = mdates.MonthLocator()   
	dayFmt = mdates.DateFormatter('%b')
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
			

	ax = plt.subplot(1,1,1)
	ax.plot(data['date'], data['tss'], 'k', label="TSS", linewidth = 0.8, alpha=0.5)
	ax.plot(data['date'], data['atl'], 'r', label="ATL", linewidth = 0.8)
	ax.plot(data['date'], data['ctl'], 'b', label="CTL", linewidth = 0.8)
	ax.plot(data['date'], data['acType'], 'go', label="type", ms=1)
	# ax.plot(data['date'], data['vo2max'], 'g', label="Vo2Max", linewidth = 0.8, alpha=0.8)

	ax.xaxis.set_major_locator(month)
	ax.xaxis.set_major_formatter(dayFmt)
	plt.xticks(rotation=70)
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()


	plt.show()

plotTsb()

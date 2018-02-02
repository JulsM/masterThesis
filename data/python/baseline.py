import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd

data = pd.read_csv('../output/Julian Maurer/baseline.csv', skipinitialspace=True, skiprows=1, names=['pred', 'label', 'dist', 'elev', 'riegel'])

fig = plt.figure(figsize=(12, 6), dpi=100, facecolor='w')
plt.subplot(211)
plt.scatter(data.label, data.pred, label='Predictor', s= 4)
# fit = np.poly1d(np.polyfit(data.label, data.pred, 1))
xp = np.linspace(15, 85, 2)
# plt.plot(xp, fit(xp), color='blue', lw=1)

plt.scatter(data.label, data.riegel, label='Riegel', s= 4, color='purple')
# fit = np.poly1d(np.polyfit(data.label, data.riegel, 1))
# plt.plot(xp, fit(xp), color='green', lw=1)
# data = data.sort_values('pred')

plt.plot(xp, xp, color='red', lw=1)

plt.xlabel('Minutes')
plt.ylabel('Minutes')
plt.grid()
plt.legend(fontsize="small", loc="upper right")



# plt.subplot(212)
ax = plt.subplot(223)
df = pd.DataFrame(data.pred-data.label)
df.plot.bar(ax=plt.gca(), label='Predictor')
plt.axhline(0, color='k')
plt.grid()
plt.xlabel('Race')
plt.ylabel('Error')
acc = 0
for i, s in df.iterrows():
	acc += (1 - (abs(s[0]) / data.iloc[i, 1])) * 100
acc = round(acc / df[0].count(), 2)
t = 'Accuracy: '+str(acc)+' %'
ax.set_title(t)

ax = plt.subplot(224)
df = pd.DataFrame(data.riegel-data.label)
df.plot.bar(ax=plt.gca(), label='Riegel', color='purple')
plt.axhline(0, color='k')
plt.grid()
plt.xlabel('Race')
plt.ylabel('Error')

acc = 0
for i, s in df.iterrows():
	acc += (1 - (abs(s[0]) / data.iloc[i, 1])) * 100
acc = round(acc / df[0].count(), 2)
t = 'Accuracy: '+str(acc)+' %'
ax.set_title(t)

plt.tight_layout()
plt.show()
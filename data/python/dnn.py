import numpy as np
import tensorflow as tf
import itertools
import pandas as pd
from scipy import stats
import matplotlib.pyplot as plt 

tf.logging.set_verbosity(tf.logging.INFO)

def normalize(train, test):
	label_train = train[LABEL]
	label_test = test[LABEL]
	mean, std = train[FEATURES].mean(axis=0), train[FEATURES].std(axis=0)
	
	train = (train[FEATURES] - mean) /std
	test = (test[FEATURES] - mean) / std
	train = pd.concat([train, label_train], axis=1)
	test = pd.concat([test, label_test], axis=1)
	return train, test

def get_input_fn(data_set, num_epochs=None, shuffle=True):
	return tf.estimator.inputs.pandas_input_fn(x=pd.DataFrame({k: data_set[k].values for k in FEATURES}), 
  		y = pd.Series(data_set[LABEL].values), num_epochs=num_epochs, shuffle=shuffle)

train_steps = 5000

COLUMNS = ["dist", "elev", "avgSpeed", "hilly", "CS", "time"]
# FEATURES = ["dist", "elev", "avgSpeed", "hilly", "CS"]
FEATURES = ["dist"]
LABEL = "time"


training_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=1, names=COLUMNS, nrows=10)
test_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=11, names=COLUMNS, nrows=5)
prediction_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=16, names=COLUMNS, nrows=1)




training_set = pd.DataFrame(training_set, columns=COLUMNS)
test_set = pd.DataFrame(test_set, columns=COLUMNS)
prediction_set = pd.DataFrame(prediction_set, columns=COLUMNS)



def plotStatistics():
	# training_set.hist()

	# training_set.plot(kind='density', subplots=True, layout=(3,3), sharex=False)

	# pd.plotting.scatter_matrix(training_set)

	# cax = plt.matshow(training_set.corr(), vmin=-1, vmax=1)
	# plt.colorbar(cax)
	# locs, labs = plt.xticks()
	# plt.xticks(locs[1:-1], COLUMNS)
	# plt.yticks(locs[1:-1], COLUMNS)


	plt.scatter(training_set['dist'], training_set['CS'])


	plt.show()



def train(training_set, test_set):
	# training_set /= 1000
	# test_set /= 1000
	# training_set, test_set = normalize(training_set, test_set)

	feature_cols = [tf.feature_column.numeric_column(k) for k in FEATURES]


	regressor = tf.estimator.DNNRegressor(feature_columns=feature_cols,hidden_units=[64, 64], 
		dropout=0.5)
	
 # optimizer=tf.train.AdamOptimizer(learning_rate=0.001)

	regressor.train(input_fn=get_input_fn(training_set, num_epochs=None, shuffle=True), steps=train_steps)



	eval_metrics = regressor.evaluate(input_fn=get_input_fn(test_set, num_epochs=1, shuffle=False))

	print("eval metrics: %r"% eval_metrics)

	y = regressor.predict(input_fn=get_input_fn(prediction_set, num_epochs=1, shuffle=False))

	predictions = list(p["predictions"] for p in itertools.islice(y, 6))
	print("Predictions: {}".format(str(predictions)))




train(training_set, test_set)
# plotStatistics()







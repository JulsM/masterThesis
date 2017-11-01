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

COLUMNS = ["dist", "elev", "vo2max", "tss", "time"]
FEATURES = ["dist", "elev", "vo2max", "tss"]
LABEL = "time"

filepath = "../output/raceFeatures.csv"

training_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=1, names=COLUMNS, nrows=10)
test_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=11, names=COLUMNS, nrows=5)
prediction_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=16, names=COLUMNS, nrows=1)




training_set = pd.DataFrame(training_set, columns=COLUMNS)
test_set = pd.DataFrame(test_set, columns=COLUMNS)
prediction_set = pd.DataFrame(prediction_set, columns=COLUMNS)

training_set, test_set = normalize(training_set, test_set)

# print(training_set)
# y_pos = [0 for i in range(len(norm_train_set[COLUMNS[0]]))]
# plt.scatter(training_set[COLUMNS[0]], training_set[COLUMNS[2]])
# plt.show()








feature_cols = [tf.feature_column.numeric_column(k) for k in FEATURES]


regressor = tf.estimator.DNNRegressor(feature_columns=feature_cols,hidden_units=[10, 10], 
	dropout=0.5, optimizer=tf.train.AdamOptimizer(learning_rate=0.001))


regressor.train(input_fn=get_input_fn(training_set, num_epochs=None, shuffle=True), steps=train_steps)


eval_metrics = regressor.evaluate(input_fn=get_input_fn(test_set, num_epochs=1, shuffle=False))

print("eval metrics: %r"% eval_metrics)

y = regressor.predict(input_fn=get_input_fn(prediction_set, num_epochs=1, shuffle=False))

predictions = list(p["predictions"] for p in itertools.islice(y, 6))
print("Predictions: {}".format(str(predictions)))






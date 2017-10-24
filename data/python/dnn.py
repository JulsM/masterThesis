import numpy as np
import tensorflow as tf
import itertools
import pandas as pd
from scipy import stats
import matplotlib.pyplot as plt 

tf.logging.set_verbosity(tf.logging.INFO)

def normalize(train):
    mean, std = train.mean(axis=0), train.std(axis=0)
    train = (train - mean) / std
    return train

def get_input_fn(data_set, num_epochs=None, shuffle=True):
	return tf.estimator.inputs.pandas_input_fn(x=pd.DataFrame({k: data_set[k].values for k in FEATURES}), 
  		y = pd.Series(data_set[LABEL].values), num_epochs=num_epochs, shuffle=shuffle)

train_steps = 5000

COLUMNS = ["dist", "elev", "time"]
FEATURES = ["dist", "elev"]
LABEL = "time"

filepath = "../output/raceFeatures.csv"

training_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=1, names=COLUMNS, nrows=10)
test_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=11, names=COLUMNS, nrows=5)
prediction_set = pd.read_csv(filepath, skipinitialspace=True, skiprows=16, names=COLUMNS, nrows=1)



training_set = pd.DataFrame(stats.zscore(training_set), columns=COLUMNS);
test_set = pd.DataFrame(stats.zscore(test_set), columns=COLUMNS);
prediction_set = pd.DataFrame(stats.zscore(prediction_set, axis=None), columns=COLUMNS);

# print(training_set)
# y_pos = [0 for i in range(len(norm_train_set[COLUMNS[0]]))]
# plt.scatter(training_set[COLUMNS[0]], training_set[COLUMNS[2]])
# plt.show()

# print(normalize(training_set))
# print(test_set)
# print(prediction_set)

feature_cols = [tf.feature_column.numeric_column(k) for k in FEATURES]


regressor = tf.estimator.DNNRegressor(feature_columns=feature_cols,hidden_units=[10, 10])


regressor.train(input_fn=get_input_fn(training_set, num_epochs=None, shuffle=True), steps=train_steps)


eval_metrics = regressor.evaluate(input_fn=get_input_fn(test_set, num_epochs=1, shuffle=False))

print("eval metrics: %r"% eval_metrics)

y = regressor.predict(input_fn=get_input_fn(prediction_set, num_epochs=1, shuffle=False))

predictions = list(p["predictions"] for p in itertools.islice(y, 6))
print("Predictions: {}".format(str(predictions)))






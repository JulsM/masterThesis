import numpy as np
import tensorflow as tf
import itertools
import pandas as pd
from scipy import stats

tf.logging.set_verbosity(tf.logging.INFO)

def normalize(train, test):
    mean, std = train.mean(), test.std()
    train = (train - mean) / std
    test = (test - mean) / std
    return train, test

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

stats.zscore(a)

# print(training_set)
# print(test_set)
# print(prediction_set)

# feature_cols = [tf.feature_column.numeric_column("x", shape=[1])]
feature_cols = [tf.feature_column.numeric_column(k) for k in FEATURES]


regressor = tf.estimator.DNNRegressor(feature_columns=feature_cols,hidden_units=[10, 10])


regressor.train(input_fn=get_input_fn(training_set, num_epochs=None, shuffle=True), steps=train_steps)


eval_metrics = regressor.evaluate(input_fn=get_input_fn(test_set, num_epochs=1, shuffle=False))

print("eval metrics: %r"% eval_metrics)

# loss_score = eval_metrics["loss"]
# print("Loss: {0:f}".format(loss_score))

y = regressor.predict(input_fn=get_input_fn(prediction_set, num_epochs=1, shuffle=False))

predictions = list(p["predictions"] for p in itertools.islice(y, 6))
print("Predictions: {}".format(str(predictions)))






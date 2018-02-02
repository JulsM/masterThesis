import numpy as np
import tensorflow as tf
import itertools
import pandas as pd
import matplotlib.pyplot as plt 
from sklearn import preprocessing
import math as math
import os, shutil
tf.logging.set_verbosity(tf.logging.ERROR)
from plotSegmentResults import plotSegments


class SegmentPredictor:
	
	def __init__(self, FLAGS={}):


		self.FLAGS = {'learning_rate': 0.01,
						'training_steps': 40000,
						'batch_size': 256,
						'model_path': "tf_segments/",
						'data_path' : 'kmeans',
						'athlete' : 'Julian Maurer'}
		for key in FLAGS:
			self.FLAGS[key] = FLAGS[key]

		self.pathDict = {'kmeans': "../output/"+self.FLAGS['athlete']+"/segmentFeatures.csv",
					'pred' : "../output/"+self.FLAGS['athlete']+"/segmentPredictions.csv"}
		
		self.FEATURE_PATH = self.pathDict[self.FLAGS['data_path']]
		self.PRED_PATH = self.pathDict['pred']
		self.COLUMNS = ['id', 'activityDistance', 'activityTime', 'activityElevation', 'isRace', 'segStartDist', 'segEndDist', 'segLength', 'segGrade', 'elevGainDone', 'segTime']
		self.FEATURES = ['activityDistance', 'activityElevation', 'isRace', 'segStartDist', 'segLength', 'segGrade', 'elevGainDone']

		self.LABEL = "segTime"

		self.training_set = None
		self.test_set = None

	

	def plotStatistics(self):
		training_set, test_set, prediction_set = loadData()
		# training_set, test_set, prediction_set = normalize(training_set, test_set, prediction_set)
		dataset = pd.concat([training_set, test_set])
		# training_set.hist()

		# dataset.plot(kind='density', subplots=True, layout=(3,3), sharex=False)

		# pd.plotting.scatter_matrix(dataset)

		cax = plt.matshow(dataset.corr(), vmin=-1, vmax=1)
		plt.colorbar(cax)
		locs, labs = plt.xticks()
		plt.xticks(np.arange(len(COLUMNS)), COLUMNS)
		plt.yticks(np.arange(len(COLUMNS)), COLUMNS)

		# plt.scatter(dataset['dist'], dataset['CS'])
		plt.show()

	def clearOldFiles(self):
		if(os.path.isdir(self.FLAGS['model_path']+self.FLAGS['athlete'])):
			filelist = [ f for f in os.listdir(self.FLAGS['model_path']+self.FLAGS['athlete']+'/')]
			for f in filelist:
				shutil.rmtree(os.path.join(self.FLAGS['model_path']+self.FLAGS['athlete']+'/', f)) 

	def normalize(self, data):	
		standCols = [x for x in self.FEATURES if x != 'isRace']
		data[standCols] = self.std_scaler.transform(data[standCols])
		return data


	
	def loadTrainData(self):
		train_data = pd.read_csv(self.FEATURE_PATH, skipinitialspace=True, skiprows=1, names=self.COLUMNS)
		train_data = pd.DataFrame(train_data, columns=self.COLUMNS)

		return train_data

	def splitKFold(self, k, i):
		races = self.train_data[self.train_data.isRace == 1]
		train = pd.DataFrame(self.train_data[self.train_data.isRace == -1], columns=self.COLUMNS)
		uniqueIds = races.id.unique()
		foldLen = int(len(uniqueIds) / k)
		start = i * foldLen
		if i + 1 == k:
			end = len(uniqueIds)
		else:
			end = (i + 1) * foldLen

		ids = uniqueIds[start:end]
		# print(ids)
		test = races[races.id.isin(ids)]
		train = train.append(races[~races.id.isin(ids)])
		# print(test)
		# print('len train: ', len(train))
		# print('len test: ', len(test))
		# print(start, end)
		# print(len(self.train_data))
		
		self.test_set = pd.DataFrame(test, columns=self.COLUMNS).reset_index(drop=True)
		self.training_set = pd.DataFrame(train, columns=self.COLUMNS).reset_index(drop=True)
		# print(self.training_set, self.test_set)



	def get_input_fn(self, data_set, num_epochs=None, shuffle=True):
			return tf.estimator.inputs.pandas_input_fn(x=pd.DataFrame({k: data_set[k].values for k in self.FEATURES}), 
		  		y = pd.Series(data_set[self.LABEL].values), batch_size=self.FLAGS['batch_size'], num_epochs=num_epochs, shuffle=shuffle)



	def model_fn(self, features, labels, mode, params):
	 

		# Connect the first hidden layer to input layer
		feature_cols = [tf.feature_column.numeric_column(k) for k in self.FEATURES]
		input_layer = tf.feature_column.input_layer(features=features, feature_columns=feature_cols)


		# Connect the first hidden layer to second hidden layer with relu
		hidden_layer = tf.layers.dense(input_layer, 5, activation=tf.nn.elu, 
			kernel_regularizer=tf.contrib.layers.l1_l2_regularizer(scale_l1=1.0, scale_l2=1.0), name='hidden_1')

		h1_vars = tf.get_collection(tf.GraphKeys.TRAINABLE_VARIABLES, 'hidden_1')
		tf.summary.histogram('kernel_1', h1_vars[0])
		tf.summary.histogram('bias_1', h1_vars[1])
		tf.summary.histogram('activation_1', hidden_layer)

		if mode == tf.estimator.ModeKeys.TRAIN:
			hidden_layer = tf.layers.dropout(hidden_layer, rate=0.75, name='dropout_1')
			tf.summary.scalar('dropout_1', tf.nn.zero_fraction(hidden_layer))



		# Connect the second hidden layer to first hidden layer with relu
		# hidden_layer = tf.layers.dense(hidden_layer, 5, activation=tf.nn.elu, 
		# 	kernel_regularizer=tf.contrib.layers.l1_l2_regularizer(scale_l1=1.0, scale_l2=1.0), name='hidden_2')

		# h2_vars = tf.get_collection(tf.GraphKeys.TRAINABLE_VARIABLES, 'hidden_2')
		# tf.summary.histogram('kernel_2', h2_vars[0])
		# tf.summary.histogram('bias_2', h2_vars[1])
		# tf.summary.histogram('activation_2', hidden_layer)

		# if mode == tf.estimator.ModeKeys.TRAIN:
		# 	hidden_layer = tf.layers.dropout(hidden_layer, rate=0.35, name='dropout_2')
		# 	tf.summary.scalar('dropout_2', tf.nn.zero_fraction(hidden_layer))



		# Connect the output layer to second hidden layer (no activation fn)
		output_layer = tf.layers.dense(hidden_layer, 1, name='output')

		# Reshape output layer to 1-dim Tensor to return predictions
		predictions = tf.reshape(output_layer, [-1])

		# Provide an estimator spec for `ModeKeys.PREDICT`.
		if mode == tf.estimator.ModeKeys.PREDICT:
			return tf.estimator.EstimatorSpec(mode=mode,predictions={self.LABEL: predictions})


		# Calculate loss using mean squared error
		loss = tf.losses.mean_squared_error(labels, predictions)

		reg_losses = tf.get_collection(tf.GraphKeys.REGULARIZATION_LOSSES)
		loss = tf.add_n([loss] + reg_losses)

		
		tf.summary.scalar("reg_loss", reg_losses[0])
		tf.summary.scalar("train_error", loss)
		tf.summary.scalar("accuracy", tf.reduce_mean(1 - tf.divide(tf.abs(tf.cast(labels, tf.float64) - tf.cast(predictions, tf.float64)), tf.cast(labels, tf.float64))))
		tf.summary.scalar("RMSE", tf.sqrt(loss))



		optimizer = tf.train.AdamOptimizer(learning_rate=params["learning_rate"])
		train_op = optimizer.minimize(loss=loss, global_step=tf.train.get_global_step())

		alpha_t = optimizer._lr * tf.sqrt(1-optimizer._beta2_power) / (1-optimizer._beta1_power)
		tf.summary.scalar("learning_rate", alpha_t)
		
		

		# Calculate root mean squared error as additional eval metric
		eval_metric_ops = {
			"mse" : tf.metrics.mean_squared_error(tf.cast(labels, tf.float64), tf.cast(predictions, tf.float64)),
			"rmse": tf.metrics.root_mean_squared_error(tf.cast(labels, tf.float64), tf.cast(predictions, tf.float64)),
			"accuracy" : tf.metrics.mean(1 - tf.divide(tf.abs(tf.cast(labels, tf.float64) - tf.cast(predictions, tf.float64)), tf.cast(labels, tf.float64))),
			"mae" : tf.metrics.mean_absolute_error(tf.cast(labels, tf.float64), tf.cast(predictions, tf.float64))
		}
		
		# Provide an estimator spec for `ModeKeys.EVAL` and `ModeKeys.TRAIN` modes.
		return tf.estimator.EstimatorSpec(mode=mode, loss=loss, train_op=train_op, eval_metric_ops=eval_metric_ops)


		


	def trainPredictor(self):
		train_input_fn = self.get_input_fn(self.training_set, num_epochs=None, shuffle=True)

		# Train
		self.estimator.train(input_fn=train_input_fn, steps=self.FLAGS['training_steps'])


	def evaluatePredictor(self):
		# Score accuracy
		# train_input_fn = self.get_input_fn(self.training_set, num_epochs=1, shuffle=False)
		# ev = self.estimator.evaluate(input_fn=train_input_fn)
		# print('Train set evaluation')
		# print("Mean Squared Error: %s sec" % ev['mse'])
		# print("Root Mean Squared Error: %s sec\n" % ev["rmse"])
		test_input_fn = self.get_input_fn(self.test_set, num_epochs=1, shuffle=False)
		ev = self.estimator.evaluate(input_fn=test_input_fn)
		print('Test set evaluation')
		print("Mean Squared Error: %s sec" % ev['mse'])
		print("Mean Absolute Error: %s sec" % ev['mae'])
		print("Root Mean Squared Error: %s sec" % ev["rmse"])
		print("Accuracy: {:.2f} % \n".format(ev['accuracy'] * 100))
		# print("R: %s" % ev["r"])
		return ev


	def predictTimes(self):
		print('\nPredictions\n')
		pred_data = pd.read_csv(self.PRED_PATH, skipinitialspace=True, skiprows=1, names=self.COLUMNS)
		prediction_set = pd.DataFrame(pred_data.copy(), columns=self.COLUMNS)
		f = list(self.FEATURES)
		f.append(self.LABEL)
		# print(prediction_set[f])
		prediction_set = self.normalize(prediction_set)

		predict_input_fn = self.get_input_fn(prediction_set, num_epochs=1, shuffle=False)
		predictions = self.estimator.predict(input_fn=predict_input_fn)
		
		racetime = 0
		accSum = []
		results = []
		for i, p in enumerate(predictions):
			# print("Predicted time %s: %s sec" % (i, round(p[self.LABEL], 2)))
			racetime += p[self.LABEL]
			vel = round((pred_data['segEndDist'][i] - pred_data['segStartDist'][i]) / p[self.LABEL], 2)
			vel = str(int(1000/vel / 60)) + ':'+str(int(1000/vel % 60))
			accuracy = round((1 - (abs(p[self.LABEL] - prediction_set[self.LABEL][i]) / prediction_set[self.LABEL][i])) * 100, 2)
			accSum.append(accuracy)
			results.append([p[self.LABEL], pred_data[self.LABEL][i], pred_data['segGrade'][i]])
			print("Predicted time %s: %s sec, offset: %s sec, Vel: %s min/km, Sim: %s %%" % (i, round(p[self.LABEL], 2), round((p[self.LABEL] - prediction_set[self.LABEL][i]), 2), vel, accuracy))
		accuracy = round((1 - (abs(racetime - pred_data['activityTime'][0]) / pred_data['activityTime'][0])) * 100, 2)
		print("Predicted ACTIVITY time: %s min, actual time: %s min, Accuracy: %s %%, mean Similarity: %s %%" % ( round(racetime/60, 2), round(pred_data['activityTime'][0]/60, 2), accuracy, round(np.mean(accSum), 2)))
		np.savetxt('../output/'+self.FLAGS['athlete']+'/studySegmentResults.csv', results, header='predTime, time, grade', delimiter=',', fmt="%s")
		plotSegments(self.FLAGS['athlete'])

	def crossValidation(self, kfold):
		kfoldMse = []
		kfoldRmse = []
		kfoldAccuracy = []
		kfoldMae = []
		activityMse = []
		activityRmse = []
		activityAccuracy = []
		activityMae = []
		activitySimilarity = []
		print('Start training\n')
		for i in range(kfold):
			print('Iteration ', i+1)
			model_params = {"learning_rate": self.FLAGS['learning_rate']}
			self.estimator = tf.estimator.Estimator(model_fn=self.model_fn, params=model_params, model_dir=self.FLAGS['model_path']+self.FLAGS['athlete']+'/temp_'+str(i))

			self.splitKFold(kfold, i)
			print('train size: ',len(self.training_set), ' test size: ', len(self.test_set))
			self.std_scaler = preprocessing.StandardScaler().fit(self.training_set[[x for x in self.FEATURES if x != 'isRace']])
			self.training_set = self.normalize(self.training_set)
			self.test_set = self.normalize(self.test_set)
			
			self.trainPredictor()
			metrics = self.evaluatePredictor()
			kfoldMse.append(metrics['mse'])
			kfoldRmse.append(metrics['rmse'])
			kfoldAccuracy.append(metrics['accuracy'])
			kfoldMae.append(metrics['mae'])
			mse, rmse, mae, meanAcc, meanSim = self.evalActivities()
			activityMse.append(mse)
			activityRmse.append(rmse)
			activityMae.append(mae)
			activityAccuracy.append(meanAcc)
			activitySimilarity.append(meanSim)

		print('\nMean Segment Metrics for {}-fold cross validation:'.format(kfold))
		print("MSE: {:.2f} sec\nMAE: {:.2f} sec\nRMSE: {:.2f} sec\nAccuracy: {:.2f} %".format(np.mean(kfoldMse), np.mean(kfoldMae), np.mean(kfoldRmse), np.mean(kfoldAccuracy) * 100))
		print('\nMean Activity Metrics for {}-fold cross validation:'.format(kfold))
		print("MSE: {:.2f} min\nMAE: {:.2f} min\nRMSE: {:.2f} min\nAccuracy: {:.2f} %\nSimilarity: {:.2f} %".format(np.mean(activityMse), np.mean(activityMae), np.mean(activityRmse), np.mean(activityAccuracy), np.mean(activitySimilarity)))

		return np.mean(kfoldMse), np.mean(kfoldMae), np.mean(kfoldRmse), np.mean(kfoldAccuracy), np.mean(activityMse), np.mean(activityMae), np.mean(activityRmse), np.mean(activityAccuracy), np.mean(activitySimilarity)

	def evalActivities(self):
		errors = []
		accuracies = []
		similarities = []
		uniqueIds = self.test_set.id.unique()
		for i in uniqueIds:
			prediction_set = pd.DataFrame(self.test_set[self.test_set.id == i], columns=self.COLUMNS).reset_index(drop=True)
			predict_input_fn = self.get_input_fn(prediction_set, num_epochs=1, shuffle=False)
			predictions = self.estimator.predict(input_fn=predict_input_fn)
		
			predTime = 0
			similarity = []
			for j, p in enumerate(predictions):
				predTime += p[self.LABEL]
				sim = round((1 - (abs(p[self.LABEL] - prediction_set[self.LABEL][j]) / prediction_set[self.LABEL][j])) * 100, 2)
				similarity.append(sim)

			activity = self.train_data[self.train_data.id == i]
			activityTime = activity['activityTime'].iloc[0]
			activityDistance = activity['activityDistance'].iloc[0]
			accuracy = round((1 - (abs(predTime - activityTime) / activityTime)) * 100, 2)
			print('Activity race time Prediction:');
			print("Predicted time: %s min, actual time: %s min, distance: %s km, Accuracy: %s %%, Similarity: %s %%" % ( round(predTime/60, 2), round(activityTime/60, 2), round(activityDistance/1000,2), accuracy, round(np.mean(similarity), 2)))
			errors.append((activityTime - predTime) / 60)
			accuracies.append(accuracy)
			similarities.append(np.mean(similarity))
		
		
		mse = np.mean(np.power(errors, 2))
		rmse = np.sqrt(mse)
		mae = np.mean(np.absolute(errors))
			
		print('\nMean activity Metrics:')
		print("MSE: {:.2f} min\nMAE: {:.2f} min\nRMSE: {:.2f} min\nAccuracy: {:.2f} %\nSimilarity: {:.2f} %\n".format(mse, mae, rmse, np.mean(accuracies), np.mean(similarities)))
		return mse, rmse, mae, np.mean(accuracies), np.mean(similarities)


	def trainCrossValidated(self, kfold):
		print('Load Data\n')
		self.train_data = self.loadTrainData()
		self.clearOldFiles()
		self.crossValidation(kfold)

	def crossValidateAll(self, dictAthletes):
		athleteMse = []
		athleteRmse = []
		athleteAccuracy = []
		athleteMae = []
		athleteActivityMse = []
		athleteActivityRmse = []
		athleteActivityAccuracy = []
		athleteActivityMae = []
		athleteActivitySimilarity = []
		athleteName = []
		for athlete, kfold in dictAthletes.items():

			self.FLAGS['athlete'] = athlete
			self.pathDict = {'kmeans': "../output/"+self.FLAGS['athlete']+"/segmentFeatures.csv",
					'pred' : "../output/"+self.FLAGS['athlete']+"/segmentPredictions.csv"}
			self.FEATURE_PATH = self.pathDict[self.FLAGS['data_path']]
			self.train_data = self.loadTrainData()
			self.clearOldFiles()
			print('\n\nAthlete ', athlete)
			kfoldMse, kfoldMae, kfoldRmse, kfoldAccuracy, activityMse, activityMae, activityRmse, activityAccuracy, activitySimilarity = self.crossValidation(kfold)

			athleteMse.append(kfoldMse)
			athleteRmse.append(kfoldRmse)
			athleteAccuracy.append(kfoldAccuracy)
			athleteMae.append(kfoldMae)
			athleteActivityMse.append(activityMse)
			athleteActivityMae.append(activityMae)
			athleteActivityRmse.append(activityRmse)
			athleteActivityAccuracy.append(activityAccuracy)
			athleteActivitySimilarity.append(activitySimilarity)
			athleteName.append(athlete)


		print('\nMean Segment Metrics over all athletes:')
		print("MSE: {:.2f} sec\nMAE: {:.2f} sec\nRMSE: {:.2f} sec\nAccuracy: {:.2f} %".format(np.mean(athleteMse), np.mean(athleteMae), np.mean(athleteRmse), np.mean(athleteAccuracy) * 100))
		print('\nMean Activity Metrics over all athletes:')
		print("MSE: {:.2f} min\nMAE: {:.2f} min\nRMSE: {:.2f} min\nAccuracy: {:.2f} %\nSimilarity: {:.2f} %".format(np.mean(athleteActivityMse), np.mean(athleteActivityMae), np.mean(athleteActivityRmse), np.mean(athleteActivityAccuracy), np.mean(athleteActivitySimilarity)))
		np.savetxt('athleteSegmentCrossVal.csv', np.column_stack((athleteActivityMae,athleteActivityRmse,athleteActivityAccuracy, athleteActivitySimilarity,athleteName)), header='MAE, RMSE, Accuracy, Similarity, Name', delimiter=',', fmt="%s")

				
	

	def trainStandard(self):
		self.training_set = self.loadTrainData()
		self.clearOldFiles()

		model_params = {"learning_rate": self.FLAGS['learning_rate']}		
		self.estimator = tf.estimator.Estimator(model_fn=self.model_fn, params=model_params, model_dir=self.FLAGS['model_path']+self.FLAGS['athlete']+'/temp')

		self.std_scaler = preprocessing.StandardScaler().fit(self.training_set[[x for x in self.FEATURES if x != 'isRace']])
		self.training_set = self.normalize(self.training_set)

		test_data = pd.read_csv(self.PRED_PATH, skipinitialspace=True, skiprows=1, names=self.COLUMNS)
		test_set = pd.DataFrame(test_data, columns=self.COLUMNS)
		self.test_set = self.normalize(test_set)
		print('train size: ',len(self.training_set), ' test size: ', len(self.test_set))

		print('Start training\n')
		self.trainPredictor()

		self.evaluatePredictor()

		self.predictTimes()

	
		
def main(unused_argv):
	names = ['Julian Maurer', 'Lauflinchen RM', 'Kai K', 'Martin Muehlhan', 'Monika Paul', 'Chris WA', 'Kai Detemple', 'Alexander Zeiner',
	'Martin B', 'Peter Petto', 'Conny Ziegler', 'Florian Daiber']
	predictor = SegmentPredictor({'training_steps': 40000, 'data_path' : 'kmeans', 'athlete' : names[8]})
	# predictor.trainCrossValidated(6)
	
	predictor.trainStandard()
	# athleteDict = {'Julian Maurer' : 4,
	# 				'Florian Daiber' : 2,
	# 				'Joachim Gross' : 4,
	# 				'Kerstin de Vries' : 2,
	# 				'Tom Holzweg' : 4,
	# 				'Thomas Buyse' : 4,
	# 				'Torsten Kohlwey' : 4,
	# 				'Markus Pfarrkircher' : 4,
	# 				'Alexander Luedemann' : 4,
	# 				'DI RK' : 4}
	# athleteDict = {'Julian Maurer' : 6,
	# 				'Florian Daiber' : 2,
	# 				'Joachim Gross' : 5,
	# 				'Kerstin de Vries' : 2,
	# 				'Tom Holzweg' : 5,
	# 				'Thomas Buyse' : 4,
	# 				'Torsten Kohlwey' : 5,
	# 				'Markus Pfarrkircher' : 3,
	# 				'Alexander Luedemann' : 4,
	# 				'DI RK' : 8,
	# 				'Yen Mertens' : 3,
	# 				'David Chow' : 4,
	# 				'Poekie' : 6,
	# 				'Benedikt Schilling' : 2,
	# 				'Falk Hofmann' : 2,
	# 				'Yvonne Dauwalder' : 4,
	# 				'Heiko G' : 4,
	# 				'Donato Lattarulo' : 4,
	# 				'Marcel Grosser' : 4,
	# 				'Rebecca Buckingham' : 5,
	# 				'Simon Weig' : 7,
	# 				'Robert Kuehne' : 4,
	# 				'Torsten Baldes' : 5,
	# 				'Julia Habitzreither' : 4,
	# 				'Alexander Weidenhaupt' : 4,
	# 				'Timo Maurer' : 3,
	# 				'Kevin Klawitter' : 2}
	# predictor.crossValidateAll(athleteDict)

if __name__ == "__main__":
	tf.app.run()
# Алгоритм решения такой:
# минимальное рассояние между точками это абсолютная величина вектора.
# Координаты я записываю в список, затем циклом прохожу по списку,
# вычисляя расстояние между введёнными точками (для этого использую ещё один цикл).
# Результаты  также записываю в список и нахожу минимальное значение

import math		# для вычисления корня
coord_points = list()		# список с координатами точек
dist = list()
while 1:
	point=input()
	if len(point) :						# если не пустая строка
		point = point.split(' ')		# вводим координаты
		coord_points.append(point)		# добавляем координаты в список
	else:
		break

number_of_points = len(coord_points)	# вычисляем длину списка
for a in range (0, number_of_points):
	for b in range(a+1, number_of_points):	#разность координат n-ой точки с n+1-ой
		abs_x = int(coord_points[b][0]) - int(coord_points[a][0])		# разность координат по Х
		abs_y = int(coord_points[b][1]) - int(coord_points[a][1])		# разность координат по Y
		dist.append(math.sqrt(pow(abs_x, 2) + pow(abs_y, 2)))	# расстояние между точками записываем в список

print(int(min(dist)))


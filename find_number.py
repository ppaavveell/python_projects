import re

what_find = input()

where_find = ''
kol = 0

# Такой способ выполняется быстрее на 10 секунд при заданном числе 100000, но ограничевается по концу range
# for x in range(1, 100000000000000):
# 		where_find += str(x)
# 		index_ = where_find.find(what_find)
# 		if (index_ == -1):
# 			continue
# 		else:
# 			break


while 1:
	kol += 1
	where_find += str(kol)
	index_ = where_find.find(what_find)
	if (index_ == -1):
		continue
	else:
		break

print(index_+1)


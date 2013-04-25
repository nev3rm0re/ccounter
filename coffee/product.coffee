class Product
  constructor: (@calories = 0, @protein = 0, @carbs = 0, @fat = 0) ->

  add: (macro) ->
     this[m] += macro[m] for own m of this

  multiply: (multiplier) ->
    copy = new Product(@calories, @protein, @carbs, @fat)
    copy[m] *= multiplier for own m of this
    return copy
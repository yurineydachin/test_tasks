require_relative "bakery"
require "test/unit"
 
class TestBakery < Test::Unit::TestCase
 
  def test_findPacksAndCount
    assert_equal({1 => 10}, findPacksAndCount(10, [1]))
    assert_equal({2 => 5}, findPacksAndCount(10, [2]))
    assert_equal(nil, findPacksAndCount(10, [3]))
    assert_equal(nil, findPacksAndCount(10, [4]))
    assert_equal({5 => 2}, findPacksAndCount(10, [5]))

    assert_equal({3 => 3, 1 => 1}, findPacksAndCount(10, [3,1]))
    assert_equal({3 => 2, 2 => 2}, findPacksAndCount(10, [3,2]))
    assert_equal({4 => 2, 2 => 1}, findPacksAndCount(10, [4,2]))
    assert_equal({6 => 1, 2 => 2}, findPacksAndCount(10, [6,2]))
    assert_equal({2 => 5}, findPacksAndCount(10, [7,2]))
    assert_equal({8 => 1, 2 => 1}, findPacksAndCount(10, [8,2]))
    assert_equal({2 => 5}, findPacksAndCount(10, [9,2]))

    assert_equal({9 => 1, 1 => 1}, findPacksAndCount(10, [9,1]))

    assert_equal({15 => 6, 9 => 1, 1 => 1}, findPacksAndCount(100, [15, 9, 1]))
  end
 
end

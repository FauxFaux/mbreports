select 
artist.id as artist_id, album.id as album_id, album.name as album_name, albumjoin.sequence as track_number, track.name as track_name, track.id as track_id
from 
artist,track,album,albumjoin
where
track.id = albumjoin.track AND
albumjoin.album = album.id AND
album.artist = artist.id AND
artist.name = 'Ennio Morricone'
order by album.name,track_number
